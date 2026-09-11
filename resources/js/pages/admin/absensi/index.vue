
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    FileText,
    ImageOff,
    Plus,
    Search,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';

import AbsensiFormDialog from '@/components/admin/AbsensiFormDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Karyawan = {
    id_karyawan: number;
    nama: string;
    nip: string;
};

type Status =
    | 'Hadir'
    | 'Telat'
    | 'Pulang Cepat'
    | 'Alpha'
    | 'Sakit'
    | 'Izin';

type Absensi = {
    id_absensi: number;
    id_karyawan: number;
    tanggal: string;
    jam_masuk: string | null;
    jam_pulang: string | null;
    foto_masuk: string | null;
    foto_pulang: string | null;
    status: Status;
    menit_telat: number;
    keterangan: string | null;
    file_pendukung: string | null;
    karyawan: Karyawan;
};

const props = defineProps<{
    absensis: {
        data: Absensi[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    karyawans: Karyawan[];
    filters: {
        search?: string;
        bulan?: string;
        status?: string;
    };
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

const search = ref(props.filters.search ?? '');
const bulanFilter = ref(props.filters.bulan ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');

function applyFilters() {
    router.get(
        '/admin/absensi',
        {
            search: search.value || undefined,
            bulan:
                bulanFilter.value === 'all'
                    ? undefined
                    : bulanFilter.value,
            status:
                statusFilter.value === 'all'
                    ? undefined
                    : statusFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});

watch([bulanFilter, statusFilter], applyFilters);

const dialogOpen = ref(false);
const editingAbsensi = ref<Absensi | null>(null);

function openCreate() {
    editingAbsensi.value = null;
    dialogOpen.value = true;
}

function openEdit(absensi: Absensi) {
    editingAbsensi.value = absensi;
    dialogOpen.value = true;
}

function hapus(absensi: Absensi) {
    if (
        confirm(
            `Hapus absensi ${absensi.karyawan.nama} tanggal ${formatTanggal(absensi.tanggal)}?`,
        )
    ) {
        router.delete(`/admin/absensi/${absensi.id_absensi}`, {
            preserveScroll: true,
        });
    }
}

function formatTanggal(tanggal: string) {
    return new Date(tanggal).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const statusBadgeClass: Record<Status, string> = {
    Hadir: 'bg-emerald-100 text-emerald-700',
    Telat: 'bg-amber-100 text-amber-700',
    Alpha: 'bg-red-100 text-red-700',
    Sakit: 'bg-sky-100 text-sky-700',
    Izin: 'bg-violet-100 text-violet-700',
    'Pulang Cepat': 'bg-orange-100 text-orange-700',
};

/*
|--------------------------------------------------------------------------
| Preview
|--------------------------------------------------------------------------
*/

const previewOpen = ref(false);
const previewUrl = ref('');
const previewType = ref<'image' | 'pdf'>('image');

function fotoUrl(path: string) {
    if (path.startsWith('http') || path.startsWith('/')) {
        return path;
    }

    return `/storage/${path}`;
}

function bukaPreview(path: string) {
    previewUrl.value = fotoUrl(path);

    const cleanPath = path.split('?')[0].toLowerCase();

    if (cleanPath.endsWith('.pdf')) {
        previewType.value = 'pdf';
    } else {
        previewType.value = 'image';
    }

    previewOpen.value = true;

    /*
     * Buat satu history state khusus untuk modal.
     * Jadi tombol Back browser bisa digunakan
     * untuk menutup modal terlebih dahulu.
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
     * Kalau modal ditutup lewat tombol X,
     * kembalikan history ke state sebelumnya.
     *
     * Kalau ditutup lewat tombol Back browser,
     * jangan panggil history.back() lagi.
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
</script>

<template>
    <Head title="Data Absensi" />

    <div class="flex flex-col gap-4">
        <!-- HEADER -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-foreground">
                Data Absensi
            </h1>

            <Button @click="openCreate">
                <Plus class="size-4" />
                Absensi
            </Button>
        </div>

        <!-- FILTER -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />

                <Input
                    v-model="search"
                    placeholder="Cari karyawan..."
                    class="pl-9"
                />
            </div>

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

            <Select v-model="statusFilter">
                <SelectTrigger class="w-[140px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="all">
                        Semua Status
                    </SelectItem>

                    <SelectItem value="Hadir">
                        Hadir
                    </SelectItem>

                    <SelectItem value="Telat">
                        Telat
                    </SelectItem>

                    <SelectItem value="Pulang Cepat">
                        Pulang Cepat
                    </SelectItem>

                    <SelectItem value="Izin">
                        Izin
                    </SelectItem>

                    <SelectItem value="Sakit">
                        Sakit
                    </SelectItem>

                    <SelectItem value="Alpha">
                        Alpha
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- TABLE -->
        <div
            class="overflow-x-auto rounded-xl border border-border bg-card"
        >
            <table class="min-w-[1400px] w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs uppercase text-muted-foreground"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">
                            Karyawan
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Jam Masuk
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Jam Pulang
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Foto Masuk
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Foto Pulang
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Status
                        </th>

                        <th class="px-4 py-3 font-medium">
                            Keterangan
                        </th>

                        <th class="px-4 py-3 font-medium">
                            File Pendukung
                        </th>

                        <th class="px-4 py-3 text-right font-medium">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-border">
                    <!-- EMPTY -->
                    <tr v-if="absensis.data.length === 0">
                        <td
                            colspan="10"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada data absensi.
                        </td>
                    </tr>

                    <!-- DATA -->
                    <tr
                        v-for="a in absensis.data"
                        :key="a.id_absensi"
                        class="hover:bg-muted/30"
                    >
                        <!-- KARYAWAN -->
                        <td class="px-4 py-3 font-medium text-foreground">
                            {{ a.karyawan.nama }}
                        </td>

                        <!-- TANGGAL -->
                        <td class="px-4 py-3">
                            {{ formatTanggal(a.tanggal) }}
                        </td>

                        <!-- JAM MASUK -->
                        <td class="px-4 py-3">
                            {{ a.jam_masuk?.slice(0, 5) ?? '-' }}
                        </td>

                        <!-- JAM PULANG -->
                        <td class="px-4 py-3">
                            {{ a.jam_pulang?.slice(0, 5) ?? '-' }}
                        </td>

                        <!-- FOTO MASUK -->
                        <td class="px-4 py-3">
                            <button
                                v-if="a.foto_masuk"
                                type="button"
                                class="block size-10 overflow-hidden rounded-md border border-border"
                                @click="bukaPreview(a.foto_masuk)"
                            >
                                <img
                                    :src="fotoUrl(a.foto_masuk)"
                                    alt="Foto masuk"
                                    class="size-full object-cover"
                                />
                            </button>

                            <span
                                v-else
                                class="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <ImageOff class="size-4" />
                            </span>
                        </td>

                        <!-- FOTO PULANG -->
                        <td class="px-4 py-3">
                            <button
                                v-if="a.foto_pulang"
                                type="button"
                                class="block size-10 overflow-hidden rounded-md border border-border"
                                @click="bukaPreview(a.foto_pulang)"
                            >
                                <img
                                    :src="fotoUrl(a.foto_pulang)"
                                    alt="Foto pulang"
                                    class="size-full object-cover"
                                />
                            </button>

                            <span
                                v-else
                                class="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <ImageOff class="size-4" />
                            </span>
                        </td>

                        <!-- STATUS -->
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusBadgeClass[a.status]"
                            >
                                {{ a.status }}
                            </span>
                        </td>

                        <!-- KETERANGAN -->
                        <td class="px-4 py-3">
                            <span
                                v-if="a.keterangan"
                                class="block max-w-[250px] whitespace-normal"
                            >
                                {{ a.keterangan }}
                            </span>

                            <span
                                v-else
                                class="text-muted-foreground"
                            >
                                -
                            </span>
                        </td>

                        <!-- FILE PENDUKUNG -->
                        <td class="px-4 py-3">
                            <button
                                v-if="a.file_pendukung"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition hover:opacity-90"
                                @click="bukaPreview(a.file_pendukung)"
                            >
                                <FileText class="size-3.5" />
                                Lihat Surat
                            </button>

                            <span
                                v-else
                                class="text-muted-foreground"
                            >
                                -
                            </span>
                        </td>

                        <!-- AKSI -->
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="openEdit(a)"
                                >
                                    Update
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="hapus(a)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span v-if="absensis.total">
                Menampilkan {{ absensis.from }}–{{ absensis.to }} dari
                {{ absensis.total }} data
            </span>

            <div class="flex gap-1">
                <template
                    v-for="(link, i) in absensis.links"
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

    <!-- FORM ABSENSI -->
    <AbsensiFormDialog
        v-model:open="dialogOpen"
        :absensi="editingAbsensi"
        :karyawans="karyawans"
    />

    <!-- PREVIEW MODAL -->
    <Dialog
        :open="previewOpen"
        @update:open="(v) => !v && tutupPreview()"
    >
        <DialogContent
            class="flex h-[90vh] max-w-5xl flex-col overflow-hidden p-0"
        >
            <!-- HEADER MODAL -->
            <div
                class="flex shrink-0 items-center justify-between border-b px-5 py-4"
            >
                <DialogTitle class="text-lg font-semibold">
                    {{
                        previewType === 'pdf'
                            ? 'Preview File Pendukung'
                            : 'Preview Foto Absensi'
                    }}
                </DialogTitle>

                <button
                    type="button"
                    class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    @click="tutupPreview()"
                >
                    <X class="size-5" />
                    <span class="sr-only">
                        Tutup
                    </span>
                </button>
            </div>

            <!-- ISI PREVIEW -->
            <div
                class="min-h-0 flex-1 overflow-auto bg-muted/20 p-4"
            >
                <!-- GAMBAR -->
                <div
                    v-if="previewType === 'image'"
                    class="flex min-h-full items-center justify-center"
                >
                    <img
                        :src="previewUrl"
                        alt="Preview foto absensi"
                        class="max-h-[calc(90vh-100px)] max-w-full rounded-lg object-contain shadow-sm"
                    />
                </div>

                <!-- PDF -->
                <iframe
                    v-else
                    :src="previewUrl"
                    title="Preview file PDF"
                    class="h-full min-h-[700px] w-full rounded-lg border bg-white"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>

