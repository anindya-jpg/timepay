
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ImageOff, Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';

import KaryawanFormDialog from '@/components/admin/KaryawanFormDialog.vue';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * ================================
 * TYPE
 * ================================
 */

type Jabatan = {
    id_jabatan: number;
    nama_jabatan: string;
};

type Karyawan = {
    id_karyawan: number;
    nip: string;
    nama: string;
    jenis_kelamin: 'L' | 'P';
    alamat: string | null;
    no_hp: string | null;
    email: string | null;
    no_rekening: string | null;
    username: string;
    foto: string | null;
    id_jabatan: number;
    tanggal_masuk: string | null;
    status_aktif: 'aktif' | 'nonaktif';
    jabatan: Jabatan | null;
};

/**
 * ================================
 * PROPS
 * ================================
 */

const props = defineProps<{
    karyawans: {
        data: Karyawan[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };

    jabatans: Jabatan[];

    filters: {
        search?: string;
        status?: string;
        id_jabatan?: string;
    };
}>();

/**
 * ================================
 * FILTER
 * ================================
 */

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const jabatanFilter = ref(props.filters.id_jabatan ?? 'all');

function applyFilters() {
    router.get(
        '/admin/karyawan',
        {
            search: search.value || undefined,
            status:
                statusFilter.value === 'all'
                    ? undefined
                    : statusFilter.value,
            id_jabatan:
                jabatanFilter.value === 'all'
                    ? undefined
                    : jabatanFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

/**
 * Debounce pencarian
 */
let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

/**
 * Filter jabatan & status
 */
watch([statusFilter, jabatanFilter], () => {
    applyFilters();
});

/**
 * ================================
 * FORM DIALOG
 * ================================
 */

const dialogOpen = ref(false);
const editingKaryawan = ref<Karyawan | null>(null);

function openCreate() {
    editingKaryawan.value = null;
    dialogOpen.value = true;
}

function openEdit(karyawan: Karyawan) {
    editingKaryawan.value = karyawan;
    dialogOpen.value = true;
}

/**
 * ================================
 * DELETE
 * ================================
 */

function hapus(karyawan: Karyawan) {
    const yakin = confirm(
        `Hapus data karyawan "${karyawan.nama}"?\n\nTindakan ini tidak bisa dibatalkan.`,
    );

    if (!yakin) {
        return;
    }

    router.delete(`/admin/karyawan/${karyawan.id_karyawan}`, {
        preserveScroll: true,
    });
}

/**
 * ================================
 * FOTO PREVIEW
 * ================================
 */

const previewOpen = ref(false);
const previewUrl = ref('');

function fotoUrl(path: string) {
    if (path.startsWith('http') || path.startsWith('/')) {
        return path;
    }

    return `/storage/${path}`;
}

function bukaPreview(path: string) {
    previewUrl.value = fotoUrl(path);
    previewOpen.value = true;
}

/**
 * ================================
 * FORMAT DATA
 * ================================
 */

function formatJenisKelamin(jenisKelamin: 'L' | 'P') {
    return jenisKelamin === 'L' ? 'Laki-laki' : 'Perempuan';
}

function formatTanggal(tanggal: string | null) {
    if (!tanggal) {
        return '-';
    }

    const date = new Date(tanggal);

    if (Number.isNaN(date.getTime())) {
        return tanggal;
    }

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Data Karyawan" />

    <div class="flex flex-col gap-4">
        <!-- ================================
             HEADER
        ================================= -->

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Data Karyawan
                </h1>

                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola data karyawan perusahaan.
                </p>
            </div>

            <Button @click="openCreate">
                <Plus class="size-4" />
                Karyawan
            </Button>
        </div>

        <!-- ================================
             FILTER
        ================================= -->

        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <!-- SEARCH -->

            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />

                <Input
                    v-model="search"
                    placeholder="Cari nama / NIP..."
                    class="pl-9"
                />
            </div>

            <!-- FILTER -->

            <div
                class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
            >
                <span>Filter:</span>

                <!-- FILTER JABATAN -->

                <Select v-model="jabatanFilter">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="Jabatan" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="all">
                            Semua Jabatan
                        </SelectItem>

                        <SelectItem
                            v-for="j in jabatans"
                            :key="j.id_jabatan"
                            :value="String(j.id_jabatan)"
                        >
                            {{ j.nama_jabatan }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- FILTER STATUS -->

                <Select v-model="statusFilter">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="all">
                            Semua Status
                        </SelectItem>

                        <SelectItem value="aktif">
                            Aktif
                        </SelectItem>

                        <SelectItem value="nonaktif">
                            Nonaktif
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- ================================
             TABLE
        ================================= -->

        <div
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <!-- Horizontal Scroll -->

            <div class="w-full overflow-x-auto">
                <table class="min-w-[1700px] w-full text-sm">
                    <!-- TABLE HEADER -->

                    <thead
                        class="bg-muted/50 text-left text-xs uppercase text-muted-foreground"
                    >
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Foto
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                NIP
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Nama
                            </th>

                        

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Alamat
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Jabatan
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                No HP
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Email
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                No Rekening
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Username
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Tanggal Masuk
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Status
                            </th>

                            <th
                                class="whitespace-nowrap px-4 py-3 text-right font-medium"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <!-- TABLE BODY -->

                    <tbody class="divide-y divide-border">
                        <!-- EMPTY DATA -->

                        <tr v-if="karyawans.data.length === 0">
                            <td
                                colspan="13"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2"
                                >
                                    <ImageOff class="size-8 opacity-50" />

                                    <span>
                                        Belum ada data karyawan.
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- DATA -->

                        <tr
                            v-for="k in karyawans.data"
                            :key="k.id_karyawan"
                            class="hover:bg-muted/30"
                        >
                            <!-- FOTO -->

                            <td class="px-4 py-3">
                                <button
                                    v-if="k.foto"
                                    type="button"
                                    class="block size-10 overflow-hidden rounded-full border border-border transition-opacity hover:opacity-80"
                                    @click="bukaPreview(k.foto)"
                                >
                                    <img
                                        :src="fotoUrl(k.foto)"
                                        :alt="k.nama"
                                        class="size-full object-cover"
                                    />
                                </button>

                                <span
                                    v-else
                                    class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"
                                >
                                    <ImageOff class="size-4" />
                                </span>
                            </td>

                            <!-- NIP -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ k.nip }}
                            </td>

                            <!-- NAMA -->

                            <td
                                class="whitespace-nowrap px-4 py-3 font-medium text-foreground"
                            >
                                {{ k.nama }}
                            </td>

                        

                            <!-- ALAMAT -->

                            <td
                                class="max-w-[250px] px-4 py-3"
                                :title="k.alamat ?? '-'"
                            >
                                <span class="line-clamp-2">
                                    {{ k.alamat ?? '-' }}
                                </span>
                            </td>

                            <!-- JABATAN -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ k.jabatan?.nama_jabatan ?? '-' }}
                            </td>

                            <!-- NO HP -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ k.no_hp ?? '-' }}
                            </td>

                            <!-- EMAIL -->

                            <td
                                class="max-w-[220px] truncate px-4 py-3"
                                :title="k.email ?? '-'"
                            >
                                {{ k.email ?? '-' }}
                            </td>

                            <!-- NO REKENING -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ k.no_rekening ?? '-' }}
                            </td>

                            <!-- USERNAME -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ k.username }}
                            </td>

                            <!-- TANGGAL MASUK -->

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ formatTanggal(k.tanggal_masuk) }}
                            </td>

                            <!-- STATUS -->

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        k.status_aktif === 'aktif'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-red-100 text-red-700'
                                    "
                                >
                                    {{
                                        k.status_aktif === 'aktif'
                                            ? 'Aktif'
                                            : 'Nonaktif'
                                    }}
                                </span>
                            </td>

                            <!-- AKSI -->

                            <td class="px-4 py-3 text-right">
                                <div
                                    class="flex justify-end gap-2"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openEdit(k)"
                                    >
                                        Update
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="hapus(k)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================
             PAGINATION
        ================================= -->

        <div
            class="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
        >
            <span v-if="karyawans.total">
                Menampilkan
                {{ karyawans.from }}–{{ karyawans.to }}
                dari
                {{ karyawans.total }}
                karyawan
            </span>

            <div class="flex flex-wrap gap-1">
                <template
                    v-for="(link, i) in karyawans.links"
                    :key="i"
                >
                    <button
                        v-if="link.url"
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs transition-colors"
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

    <!-- ================================
         FORM TAMBAH / UPDATE
    ================================= -->

    <KaryawanFormDialog
        v-model:open="dialogOpen"
        :karyawan="editingKaryawan"
        :jabatans="jabatans"
    />

    <!-- ================================
         FOTO PREVIEW
    ================================= -->

    <Dialog
        :open="previewOpen"
        @update:open="(value) => (previewOpen = value)"
    >
        <DialogContent class="max-w-md">
            <DialogTitle>
                Foto Karyawan
            </DialogTitle>

            <img
                :src="previewUrl"
                alt="Foto karyawan"
                class="w-full rounded-lg object-contain"
            />
        </DialogContent>
    </Dialog>
</template>

