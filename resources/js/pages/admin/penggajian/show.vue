<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileDown } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Jabatan = { id_jabatan: number; nama_jabatan: string };
type Karyawan = { id_karyawan: number; nama: string; nip: string; jabatan: Jabatan };
type Penggajian = {
    id_gaji: number;
    bulan: number;
    tahun: number;
    gaji_pokok: number;
    total_tunjangan: number;
    total_jam_lembur: number;
    total_tunjangan_lembur: number;
    total_potongan: number;
    jumlah_hadir: number;
    jumlah_telat: number;
    jumlah_alpha: number;
    gaji_bersih: number;
    status: 'draft' | 'final';
    tanggal_generate: string | null;
    karyawan: Karyawan;
};

const props = defineProps<{
    penggajian: Penggajian;
}>();

const namaBulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
</script>

<template>
    <Head :title="`Slip Gaji - ${penggajian.karyawan.nama}`" />

    <div class="mx-auto flex max-w-2xl flex-col gap-4">
        <Link
            href="/admin/penggajian"
            class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke Penggajian
        </Link>

        <Card>
            <CardHeader class="flex flex-row items-start justify-between border-b border-border pb-4">
                <div>
                    <CardTitle class="text-xl">{{ penggajian.karyawan.nama }}</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        {{ penggajian.karyawan.nip }} · {{ penggajian.karyawan.jabatan.nama_jabatan }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Periode {{ namaBulan[penggajian.bulan] }} {{ penggajian.tahun }}
                    </p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="penggajian.status === 'final' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                >
                    {{ penggajian.status === 'final' ? 'FINAL' : 'DRAFT' }}
                </span>
            </CardHeader>

            <CardContent class="flex justify-end pt-4">
                <Button as-child variant="outline" size="sm">
                    <a :href="`/admin/penggajian/${penggajian.id_gaji}/pdf`">
                        <FileDown class="size-4" />
                        Download PDF
                    </a>
                </Button>
            </CardContent>

            <CardContent class="flex flex-col gap-6 pt-4">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="rounded-lg bg-muted/50 p-3">
                        <p class="text-2xl font-semibold text-foreground">{{ penggajian.jumlah_hadir }}</p>
                        <p class="text-xs text-muted-foreground">Hari Hadir</p>
                    </div>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <p class="text-2xl font-semibold text-amber-600">{{ penggajian.jumlah_telat }}</p>
                        <p class="text-xs text-muted-foreground">Kali Telat</p>
                    </div>
                    <div class="rounded-lg bg-muted/50 p-3">
                        <p class="text-2xl font-semibold text-destructive">{{ penggajian.jumlah_alpha }}</p>
                        <p class="text-xs text-muted-foreground">Hari Alpha</p>
                    </div>
                </div>

                <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm text-muted-foreground">Gaji Pokok</span>
                        <span class="font-medium text-foreground">{{ formatRupiah(penggajian.gaji_pokok) }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm text-muted-foreground">Total Tunjangan</span>
                        <span class="font-medium text-emerald-600">+ {{ formatRupiah(penggajian.total_tunjangan) }}</span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm text-muted-foreground">
                            Tunjangan Lembur ({{ penggajian.total_jam_lembur }} jam)
                        </span>
                        <span class="font-medium text-emerald-600">
                            + {{ formatRupiah(penggajian.total_tunjangan_lembur) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <span class="text-sm text-muted-foreground">Total Potongan</span>
                        <span class="font-medium text-destructive">- {{ formatRupiah(penggajian.total_potongan) }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-muted/40 px-4 py-4">
                        <span class="text-base font-semibold text-foreground">Gaji Bersih</span>
                        <span class="text-lg font-bold text-primary">{{ formatRupiah(penggajian.gaji_bersih) }}</span>
                    </div>
                </div>

                <p v-if="penggajian.tanggal_generate" class="text-xs text-muted-foreground">
                    Digenerate pada {{ new Date(penggajian.tanggal_generate).toLocaleString('id-ID') }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>