<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileText, ImageOff } from '@lucide/vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Absensi = {
    id_absensi: number;
    tanggal: string;
    jam_masuk: string | null;
    jam_pulang: string | null;
    foto_masuk: string | null;
    foto_pulang: string | null;
    menit_telat: number;
    jam_lembur: number;
    status: string;
    jarak_masuk_meter: number | null;
    jarak_pulang_meter: number | null;
    keterangan: string | null;
    file_pendukung: string | null;
};

const props = defineProps<{
    riwayat: {
        data: Absensi[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { bulan: number | null; tahun: number | null };
}>();

const namaBulan = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const bulanFilter = ref(
    props.filters.bulan ? String(props.filters.bulan) : 'all',
);

const tahunFilter = ref(
    props.filters.tahun ? String(props.filters.tahun) : 'all',
);

const tahunOptions = Array.from(
    { length: 3 },
    (_, i) => String(new Date().getFullYear() - 1 + i),
);

watch([bulanFilter, tahunFilter], () => {
    router.get(
        '/karyawan/riwayat-absensi',
        {
            bulan:
                bulanFilter.value === 'all'
                    ? undefined
                    : bulanFilter.value,
            tahun:
                tahunFilter.value === 'all'
                    ? undefined
                    : tahunFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
});

function formatTanggal(tanggal: string) {
    return new Date(tanggal).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const statusBadgeClass: Record<string, string> = {
    Hadir: 'bg-emerald-100 text-emerald-700',
    Telat: 'bg-amber-100 text-amber-700',
    Alpha: 'bg-red-100 text-red-700',
    Sakit: 'bg-sky-100 text-sky-700',
    Izin: 'bg-violet-100 text-violet-700',
    'Pulang Cepat': 'bg-orange-100 text-orange-700',
};

/*
|--------------------------------------------------------------------------
| Preview Foto / Surat
|--------------------------------------------------------------------------
*/

const previewOpen = ref(false);
const previewUrl = ref('');
const previewType = ref<'image' | 'pdf'>('image');
const previewTitle = ref('Foto Absensi');

function fotoUrl(path: string) {
    return path.startsWith('http') || path.startsWith('/')
        ? path
        : `/storage/${path}`;
}

function isPdf(path: string) {
    return path
        .split('?')[0]
        .toLowerCase()
        .endsWith('.pdf');
}

function bukaPreview(path: string, title = 'Foto Absensi') {
    previewUrl.value = fotoUrl(path);

    previewType.value = isPdf(path) ? 'pdf' : 'image';

    previewTitle.value = isPdf(path)
        ? 'Preview Surat'
        : title;

    previewOpen.value = true;

    /*
     * Tambahkan state history agar tombol Back browser
     * dapat digunakan untuk menutup preview terlebih dahulu.
     */
    window.history.pushState(
        {
            ...(window.history.state ?? {}),
            timepayPreview: true,
        },
        '',
        window.location.href,
    );
}

function tutupPreview(fromPopState = false) {
    if (!previewOpen.value) {
        return;
    }

    previewOpen.value = false;
    previewUrl.value = '';

    /*
     * Kalau preview ditutup lewat tombol X,
     * kembalikan history yang tadi ditambahkan.
     */
    if (
        !fromPopState &&
        window.history.state?.timepayPreview
    ) {
        window.history.back();
    }
}

function handlePopState() {
    if (previewOpen.value) {
        tutupPreview(true);
    }
}

onMounted(() => {
    window.addEventListener('popstate', handlePopState);
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
});

/*
|--------------------------------------------------------------------------
| Drag-to-Scroll
|--------------------------------------------------------------------------
|
| Tabel bisa digeser menggunakan mouse di desktop.
|
*/

const scrollContainer = ref<HTMLDivElement | null>(null);

let isDragging = false;
let startX = 0;
let scrollLeftAwal = 0;
let sudahGeser = false;

function mulaiDrag(event: MouseEvent) {
    if (!scrollContainer.value) {
        return;
    }

    isDragging = true;
    sudahGeser = false;

    startX =
        event.pageX -
        scrollContainer.value.offsetLeft;

    scrollLeftAwal =
        scrollContainer.value.scrollLeft;

    scrollContainer.value.classList.add(
        'cursor-grabbing',
    );

    scrollContainer.value.classList.remove(
        'cursor-grab',
    );
}

function prosesDrag(event: MouseEvent) {
    if (!isDragging || !scrollContainer.value) {
        return;
    }

    event.preventDefault();

    const x =
        event.pageX -
        scrollContainer.value.offsetLeft;

    const jarak = x - startX;

    if (Math.abs(jarak) > 5) {
        sudahGeser = true;
    }

    scrollContainer.value.scrollLeft =
        scrollLeftAwal - jarak;
}

function akhiriDrag() {
    if (!scrollContainer.value) {
        return;
    }

    isDragging = false;

    scrollContainer.value.classList.remove(
        'cursor-grabbing',
    );

    scrollContainer.value.classList.add(
        'cursor-grab',
    );
}

function cegahKlikSetelahDrag(event: MouseEvent) {
    if (sudahGeser) {
        event.preventDefault();
        event.stopPropagation();

        sudahGeser = false;
    }
}
</script>

<template>
    <Head title="Riwayat Absensi" />

    <div class="flex flex-col gap-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <h1
                class="text-2xl font-semibold text-foreground"
            >
                Riwayat Absensi
            </h1>

            <div class="flex items-center gap-2">
                <Select v-model="bulanFilter">
                    <SelectTrigger class="w-[150px]">
                        <SelectValue placeholder="Bulan" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="all">
                            Semua Bulan
                        </SelectItem>

                        <SelectItem
                            v-for="(nama, i) in namaBulan"
                            :key="i"
                            :value="String(i + 1)"
                        >
                            {{ nama }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="tahunFilter">
                    <SelectTrigger class="w-[110px]">
                        <SelectValue placeholder="Tahun" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="all">
                            Semua Tahun
                        </SelectItem>

                        <SelectItem
                            v-for="t in tahunOptions"
                            :key="t"
                            :value="t"
                        >
                            {{ t }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <Card>
            <CardContent class="px-0 py-0">
                <div
                    ref="scrollContainer"
                    class="cursor-grab overflow-x-auto select-none"
                    @mousedown="mulaiDrag"
                    @mousemove="prosesDrag"
                    @mouseup="akhiriDrag"
                    @mouseleave="akhiriDrag"
                    @click.capture="cegahKlikSetelahDrag"
                >
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-border bg-muted/50 text-left text-xs uppercase text-muted-foreground"
                        >
                            <tr>
                                <th class="px-6 py-3 font-medium">
                                    Tanggal
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Jam Masuk
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Jam Pulang
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Terlambat
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Lembur
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Foto
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Status
                                </th>

                                <th class="px-6 py-3 font-medium">
                                    Keterangan
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-border"
                        >
                            <tr
                                v-if="
                                    riwayat.data.length === 0
                                "
                            >
                                <td
                                    colspan="8"
                                    class="px-6 py-10 text-center text-muted-foreground"
                                >
                                    Belum ada riwayat
                                    absensi.
                                </td>
                            </tr>

                            <tr
                                v-for="a in riwayat.data"
                                :key="a.id_absensi"
                                class="hover:bg-muted/30"
                            >
                                <td
                                    class="px-6 py-3 font-medium text-foreground"
                                >
                                    {{
                                        formatTanggal(
                                            a.tanggal,
                                        )
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    {{
                                        a.jam_masuk?.slice(
                                            0,
                                            5,
                                        ) ?? '-'
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    {{
                                        a.jam_pulang?.slice(
                                            0,
                                            5,
                                        ) ?? '-'
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        :class="
                                            a.menit_telat >
                                            0
                                                ? 'text-amber-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            a.menit_telat >
                                            0
                                                ? `${a.menit_telat} mnt`
                                                : '-'
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        :class="
                                            a.jam_lembur >
                                            0
                                                ? 'text-emerald-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            a.jam_lembur >
                                            0
                                                ? `${a.jam_lembur} jam`
                                                : '-'
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-3">
                                    <div class="flex gap-1.5">
                                        <button
                                            v-if="
                                                a.foto_masuk
                                            "
                                            type="button"
                                            class="block size-8 overflow-hidden rounded-md border border-border"
                                            title="Foto masuk"
                                            @click="
                                                bukaPreview(
                                                    a.foto_masuk,
                                                    'Foto Masuk',
                                                )
                                            "
                                        >
                                            <img
                                                :src="
                                                    fotoUrl(
                                                        a.foto_masuk,
                                                    )
                                                "
                                                alt="Foto masuk"
                                                class="size-full object-cover"
                                                draggable="false"
                                            />
                                        </button>

                                        <button
                                            v-if="
                                                a.foto_pulang
                                            "
                                            type="button"
                                            class="block size-8 overflow-hidden rounded-md border border-border"
                                            title="Foto pulang"
                                            @click="
                                                bukaPreview(
                                                    a.foto_pulang,
                                                    'Foto Pulang',
                                                )
                                            "
                                        >
                                            <img
                                                :src="
                                                    fotoUrl(
                                                        a.foto_pulang,
                                                    )
                                                "
                                                alt="Foto pulang"
                                                class="size-full object-cover"
                                                draggable="false"
                                            />
                                        </button>

                                        <span
                                            v-if="
                                                !a.foto_masuk &&
                                                !a.foto_pulang
                                            "
                                            class="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground"
                                        >
                                            <ImageOff
                                                class="size-3.5"
                                            />
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            statusBadgeClass[
                                                a.status
                                            ] ??
                                            'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{ a.status }}
                                    </span>
                                </td>

                                <td
                                    class="max-w-[200px] px-6 py-3"
                                >
                                    <p
                                        v-if="
                                            a.keterangan
                                        "
                                        class="truncate text-xs text-muted-foreground"
                                        :title="
                                            a.keterangan
                                        "
                                    >
                                        {{
                                            a.keterangan
                                        }}
                                    </p>

                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground/50"
                                    >
                                        -
                                    </span>

                                    <button
                                        v-if="
                                            a.file_pendukung
                                        "
                                        type="button"
                                        class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                        @click="
                                            bukaPreview(
                                                a.file_pendukung,
                                            )
                                        "
                                    >
                                        <FileText
                                            class="size-3.5"
                                        />

                                        Lihat Surat
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span v-if="riwayat.total">
                Menampilkan {{ riwayat.from }}–{{
                    riwayat.to
                }}
                dari {{ riwayat.total }} data
            </span>

            <div class="flex gap-1">
                <template
                    v-for="(link, i) in riwayat.links"
                    :key="i"
                >
                    <button
                        v-if="link.url"
                        class="rounded-md px-3 py-1.5 text-xs"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        v-html="link.label"
                        @click="
                            router.get(
                                link.url,
                                {},
                                {
                                    preserveState: true,
                                    preserveScroll: true,
                                },
                            )
                        "
                    />

                    <span
                        v-else
                        class="rounded-md px-3 py-1.5 text-xs text-muted-foreground/50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <!--
    |--------------------------------------------------------------------------
    | Preview Foto / Surat
    |--------------------------------------------------------------------------
    -->

    <Dialog
        :open="previewOpen"
        @update:open="
            (v) => {
                if (!v) {
                    tutupPreview();
                }
            }
        "
    >
        <DialogContent
            class="flex h-[90vh] max-w-5xl flex-col"
        >
            <DialogTitle>
                {{ previewTitle }}
            </DialogTitle>

            <div
                class="min-h-0 flex-1 overflow-hidden"
            >
                <!-- Preview Foto -->
                <img
                    v-if="
                        previewType === 'image'
                    "
                    :src="previewUrl"
                    :alt="previewTitle"
                    class="h-full w-full rounded-lg object-contain"
                />

                <!-- Preview PDF -->
                <iframe
                    v-else
                    :src="previewUrl"
                    :title="previewTitle"
                    class="h-full w-full rounded-lg border border-border"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>