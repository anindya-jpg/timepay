
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, LogIn, User as UserIcon } from '@lucide/vue';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Jabatan = {
    nama_jabatan: string;
};

type Karyawan = {
    id_karyawan: number;
    nama: string;
    nip: string;
    foto: string | null;
    status_aktif: 'aktif' | 'nonaktif';
    jabatan?: Jabatan | null;
};

type Absensi = {
    id_absensi: number;
    tanggal: string;
    jam_masuk: string | null;
    jam_pulang: string | null;
    menit_telat: number;
    jam_lembur: number;
    status: string;
};

type Penggajian = {
    gaji_pokok: number;
    total_tunjangan: number;
    total_potongan: number;
    gaji_bersih: number;
    status: 'draft' | 'final';
} | null;

const props = defineProps<{
    karyawan: Karyawan;
    filters: {
        bulan: number;
        tahun: number;
    };
    ringkasanKehadiran: {
        rata_rata_jam_masuk: string | null;
        rata_rata_jam_pulang: string | null;
        total_menit_telat: number;
    };
    penggajian: Penggajian;
    riwayatTerakhir: Absensi[];
    jadwalCuti: {
        id_libur: number;
        tanggal: string;
        keterangan: string | null;
    }[];
}>();

const namaBulan = [
    '',
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

const bulanFilter = ref(String(props.filters.bulan));
const tahunFilter = ref(String(props.filters.tahun));

const tahunSekarang = new Date().getFullYear();

const tahunOptions = Array.from(
    { length: 3 },
    (_, i) => String(tahunSekarang - 1 + i),
);

watch(
    [bulanFilter, tahunFilter],
    () => {
        router.get(
            '/karyawan/dashboard',
            {
                bulan: bulanFilter.value,
                tahun: tahunFilter.value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    },
);

function formatRupiah(angka: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(angka);
}

function formatTanggalPendek(tanggal: string): string {
    return new Date(tanggal).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
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

function statusLabel(absensi: Absensi): string {
    if (absensi.jam_lembur > 0 && absensi.status === 'Hadir') {
        return 'Hadir + Lembur';
    }

    return absensi.status;
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6">
        <!-- Kartu Sambutan -->
        <div
            class="flex flex-col gap-4 rounded-xl bg-primary p-6 text-primary-foreground sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div
                    class="flex size-14 items-center justify-center overflow-hidden rounded-full bg-white/10 ring-2 ring-white/20"
                >
                    <img
                        v-if="karyawan.foto"
                        :src="
                            karyawan.foto.startsWith('http')
                                ? karyawan.foto
                                : `/storage/${karyawan.foto}`
                        "
                        :alt="karyawan.nama"
                        class="size-full object-cover"
                    />

                    <UserIcon v-else class="size-6" />
                </div>

                <div>
                    <h1 class="text-xl font-bold">
                        Halo, {{ karyawan.nama }}
                    </h1>

                    <p class="text-sm text-primary-foreground/70">
                        NIP: {{ karyawan.nip }}
                        &middot;
                        {{ karyawan.jabatan?.nama_jabatan ?? '-' }}
                        &middot;
                        Status
                        {{
                            karyawan.status_aktif === 'aktif'
                                ? 'Aktif'
                                : 'Nonaktif'
                        }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button
                    as-child
                    variant="outline"
                    class="border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
                >
                    <Link href="/karyawan/slip-gaji">
                        <Download class="size-4" />
                        Download Slip Gaji
                    </Link>
                </Button>

                <Button
                    as-child
                    class="bg-accent text-accent-foreground hover:bg-accent/90"
                >
                    <Link href="/karyawan/absen-masuk">
                        <LogIn class="size-4" />
                        Absen Sekarang
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Ringkasan Kehadiran -->
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-foreground">
                    Ringkasan Kehadiran Karyawan
                </h2>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">
                        Periode:
                    </span>

                    <Select v-model="bulanFilter">
                        <SelectTrigger class="w-[130px]">
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="i in 12"
                                :key="i"
                                :value="String(i)"
                            >
                                {{ namaBulan[i] }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="tahunFilter">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="tahun in tahunOptions"
                                :key="tahun"
                                :value="tahun"
                            >
                                {{ tahun }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <!-- Rata-rata Jam Masuk -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Rata-rata Jam Masuk
                        </p>

                        <p
                            class="mt-1 text-2xl font-bold text-foreground"
                        >
                            {{
                                ringkasanKehadiran.rata_rata_jam_masuk ?? '-'
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Rata-rata Jam Pulang -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Rata-rata Jam Pulang
                        </p>

                        <p
                            class="mt-1 text-2xl font-bold text-foreground"
                        >
                            {{
                                ringkasanKehadiran.rata_rata_jam_pulang ?? '-'
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Menit Telat -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Total Menit Telat
                        </p>

                        <p
                            class="mt-1 text-2xl font-bold text-foreground"
                        >
                            {{ ringkasanKehadiran.total_menit_telat }}
                            Menit
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Informasi Penggajian -->
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-foreground">
                    Informasi Penggajian Utama
                </h2>

                <span
                    v-if="penggajian"
                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="
                        penggajian.status === 'final'
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-amber-100 text-amber-700'
                    "
                >
                    Status:
                    {{
                        penggajian.status === 'final'
                            ? 'Sudah Dibayar'
                            : 'Draft'
                    }}
                </span>
            </div>

            <div
                v-if="penggajian"
                class="grid gap-3 sm:grid-cols-4"
            >
                <!-- Gaji Pokok -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Gaji Pokok
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-foreground"
                        >
                            {{
                                formatRupiah(
                                    penggajian.gaji_pokok,
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Tunjangan -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Tunjangan
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-foreground"
                        >
                            {{
                                formatRupiah(
                                    penggajian.total_tunjangan,
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Potongan -->
                <Card>
                    <CardContent class="pt-6">
                        <p class="text-xs text-muted-foreground">
                            Potongan
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-destructive"
                        >
                            {{
                                formatRupiah(
                                    penggajian.total_potongan,
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Gaji Bersih -->
                <Card
                    class="bg-primary text-primary-foreground"
                >
                    <CardContent class="pt-6">
                        <p
                            class="text-xs text-primary-foreground/70"
                        >
                            Gaji Bersih
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-accent"
                        >
                            {{
                                formatRupiah(
                                    penggajian.gaji_bersih,
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card v-else>
                <CardContent
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Belum ada data penggajian untuk periode ini.
                </CardContent>
            </Card>
        </div>

        <!-- Riwayat Absensi -->
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between"
            >
                <CardTitle class="text-base">
                    Riwayat Absensi Terakhir
                </CardTitle>

                <Link
                    href="/karyawan/riwayat-absensi"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    Lihat Semua Riwayat
                </Link>
            </CardHeader>

            <CardContent class="px-0 pt-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-y border-border bg-muted/50 text-left text-xs uppercase text-muted-foreground"
                        >
                            <tr>
                                <th class="px-6 py-2.5 font-medium">
                                    Tanggal
                                </th>
                                <th class="px-6 py-2.5 font-medium">
                                    Jam Masuk
                                </th>
                                <th class="px-6 py-2.5 font-medium">
                                    Jam Pulang
                                </th>
                                <th class="px-6 py-2.5 font-medium">
                                    Terlambat
                                </th>
                                <th class="px-6 py-2.5 font-medium">
                                    Lembur
                                </th>
                                <th class="px-6 py-2.5 font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-border"
                        >
                            <tr
                                v-if="
                                    riwayatTerakhir.length === 0
                                "
                            >
                                <td
                                    colspan="6"
                                    class="px-6 py-6 text-center text-muted-foreground"
                                >
                                    Belum ada riwayat absensi.
                                </td>
                            </tr>

                            <tr
                                v-for="a in riwayatTerakhir"
                                :key="a.id_absensi"
                            >
                                <td class="px-6 py-3">
                                    {{
                                        formatTanggalPendek(
                                            a.tanggal,
                                        )
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    {{
                                        a.jam_masuk?.slice(0, 5) ??
                                        '-'
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    {{
                                        a.jam_pulang?.slice(0, 5) ??
                                        '-'
                                    }}
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        :class="
                                            a.menit_telat > 0
                                                ? 'text-amber-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            a.menit_telat > 0
                                                ? `${a.menit_telat} mnt`
                                                : '-'
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-3">
                                    <span
                                        :class="
                                            a.jam_lembur > 0
                                                ? 'text-emerald-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{
                                            a.jam_lembur > 0
                                                ? `${a.jam_lembur} jam`
                                                : '-'
                                        }}
                                    </span>
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
                                        {{ statusLabel(a) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Jadwal Cuti / Hari Libur -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">
                    Jadwal Cuti Mendatang
                </CardTitle>
            </CardHeader>

            <CardContent class="flex flex-col gap-3">
                <p
                    v-if="jadwalCuti.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Tidak ada jadwal libur dalam waktu dekat.
                </p>

                <div
                    v-for="cuti in jadwalCuti"
                    :key="cuti.id_libur"
                    class="flex items-center justify-between rounded-lg border border-border px-4 py-3"
                >
                    <div>
                        <p
                            class="text-sm font-medium text-foreground"
                        >
                            {{
                                cuti.keterangan ??
                                'Hari Libur'
                            }}
                        </p>

                        <p
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                formatTanggalPendek(
                                    cuti.tanggal,
                                )
                            }}
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                    >
                        Libur Nasional
                    </span>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

