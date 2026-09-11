<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Sparkles, FileDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Karyawan = { id_karyawan: number; nama: string; nip: string };
type Status = 'draft' | 'final';
type Penggajian = {
    id_gaji: number;
    gaji_pokok: number;
    total_tunjangan: number;
    total_jam_lembur: number;
    total_tunjangan_lembur: number;
    total_potongan: number;
    gaji_bersih: number;
    status: Status;
    karyawan: Karyawan;
};

const props = defineProps<{
    penggajians: Penggajian[];
    filters: { bulan: number; tahun: number };
}>();

const namaBulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const tahunFilter = ref(String(props.filters.tahun));
const bulanFilter = ref(String(props.filters.bulan));
const tahunOptions = Array.from({ length: 5 }, (_, i) => String(new Date().getFullYear() - 2 + i));

function pindahPeriode() {
    router.get(
        '/admin/penggajian',
        { tahun: tahunFilter.value, bulan: bulanFilter.value },
        { preserveState: true, replace: true },
    );
}

const isGenerating = ref(false);

const rekapPdfUrl = computed(
    () => `/admin/penggajian/rekap/pdf?tahun=${tahunFilter.value}&bulan=${bulanFilter.value}`,
);

function generateGaji() {
    if (props.penggajians.length > 0) {
        if (!confirm(`Gaji untuk ${namaBulan[props.filters.bulan]} ${props.filters.tahun} sudah pernah digenerate. Generate ulang (data lama akan ditimpa untuk yang masih draft)?`)) {
            return;
        }
    }
    isGenerating.value = true;
    router.post(
        '/admin/penggajian/generate',
        { bulan: bulanFilter.value, tahun: tahunFilter.value },
        {
            preserveScroll: true,
            onFinish: () => (isGenerating.value = false),
        },
    );
}

function finalisasiSemua() {
    if (confirm(`Finalisasi SEMUA gaji ${namaBulan[props.filters.bulan]} ${props.filters.tahun}? Setelah final, data tidak bisa digenerate ulang.`)) {
        router.post(
            '/admin/penggajian/finalisasi',
            { bulan: bulanFilter.value, tahun: tahunFilter.value },
            { preserveScroll: true },
        );
    }
}

const jumlahFinal = computed(() => props.penggajians.filter((p) => p.status === 'final').length);
const jumlahDraft = computed(() => props.penggajians.filter((p) => p.status === 'draft').length);
const semuaFinal = computed(() => props.penggajians.length > 0 && jumlahDraft.value === 0);

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
</script>

<template>
    <Head title="Penggajian" />

    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold text-foreground">Penggajian</h1>

        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm text-muted-foreground">Periode Penggajian</span>
            <Select v-model="tahunFilter" @update:model-value="pindahPeriode">
                <SelectTrigger class="w-[120px]">
                    <SelectValue placeholder="Tahun" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="t in tahunOptions" :key="t" :value="t">{{ t }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="bulanFilter" @update:model-value="pindahPeriode">
                <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Bulan" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="i in 12" :key="i" :value="String(i)">{{ namaBulan[i] }}</SelectItem>
                </SelectContent>
            </Select>

            <Button :disabled="isGenerating" @click="generateGaji">
                <Sparkles class="size-4" />
                {{ isGenerating ? 'Memproses...' : 'Generate Gaji' }}
            </Button>

            <Button v-if="penggajians.length > 0" as-child variant="outline">
                <a :href="rekapPdfUrl">
                    <FileDown class="size-4" />
                    Download Rekap PDF
                </a>
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Karyawan</th>
                        <th class="px-4 py-3 font-medium">Gaji Pokok</th>
                        <th class="px-4 py-3 font-medium">Tunjangan</th>
                        <th class="px-4 py-3 font-medium">Lembur</th>
                        <th class="px-4 py-3 font-medium">Potongan</th>
                        <th class="px-4 py-3 font-medium">Gaji Bersih</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="penggajians.length === 0">
                        <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">
                            Belum ada data gaji untuk periode ini. Klik "Generate Gaji" untuk membuatnya.
                        </td>
                    </tr>
                    <tr v-for="p in penggajians" :key="p.id_gaji" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ p.karyawan.nama }}</td>
                        <td class="px-4 py-3">{{ formatRupiah(p.gaji_pokok) }}</td>
                        <td class="px-4 py-3">{{ formatRupiah(p.total_tunjangan) }}</td>
                        <td class="px-4 py-3">
                            {{ formatRupiah(p.total_tunjangan_lembur) }}
                            <span class="text-xs text-muted-foreground">({{ p.total_jam_lembur }} jam)</span>
                        </td>
                        <td class="px-4 py-3 text-destructive">- {{ formatRupiah(p.total_potongan) }}</td>
                        <td class="px-4 py-3 font-semibold text-foreground">{{ formatRupiah(p.gaji_bersih) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="p.status === 'final' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            >
                                {{ p.status === 'final' ? 'Final' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/admin/penggajian/${p.id_gaji}`"
                                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                            >
                                Lihat Detail
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="penggajians.length > 0" class="flex items-center justify-between">
            <p class="text-sm font-medium">
                Status:
                <span :class="semuaFinal ? 'text-emerald-600' : 'text-amber-600'">
                    {{ semuaFinal ? 'FINAL' : 'DRAFT' }}
                </span>
                <span class="ml-2 text-muted-foreground">
                    ({{ jumlahFinal }} Final, {{ jumlahDraft }} Draft)
                </span>
            </p>
            <Button variant="destructive" :disabled="semuaFinal" @click="finalisasiSemua">
                Finalisasi Semua
            </Button>
        </div>
    </div>
</template>