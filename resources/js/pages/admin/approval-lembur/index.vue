<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Eye, ImageOff, Pencil, Search, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import ApprovalLemburFormDialog from '@/components/admin/ApprovalLemburFormDialog.vue';
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

type Karyawan = { id_karyawan: number; nama: string; nip: string };
type StatusApproval = 'pending' | 'approved' | 'rejected';
type Lembur = {
    id_absensi: number;
    tanggal: string;
    jam_lembur: number;
    keterangan_lembur: string | null;
    foto_pulang: string | null;
    status_approval_lembur: StatusApproval;
    karyawan: Karyawan;
};

const props = defineProps<{
    lemburs: {
        data: Lembur[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; tahun?: string; bulan?: string; status?: string };
}>();

const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const search = ref(props.filters.search ?? '');
const tahunFilter = ref(props.filters.tahun ?? 'all');
const bulanFilter = ref(props.filters.bulan ?? 'all');
const statusFilter = ref(props.filters.status ?? 'pending');

const tahunOptions = Array.from({ length: 5 }, (_, i) => String(new Date().getFullYear() - 2 + i));

function applyFilters() {
    router.get(
        '/admin/approval-lembur',
        {
            search: search.value || undefined,
            tahun: tahunFilter.value === 'all' ? undefined : tahunFilter.value,
            bulan: bulanFilter.value === 'all' ? undefined : bulanFilter.value,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});
watch([tahunFilter, bulanFilter, statusFilter], applyFilters);

function approve(lembur: Lembur) {
    if (confirm(`Setujui lembur ${lembur.karyawan.nama} (${lembur.jam_lembur} jam) tanggal ${formatTanggal(lembur.tanggal)}?`)) {
        router.post(`/admin/approval-lembur/${lembur.id_absensi}/approve`, {}, { preserveScroll: true });
    }
}

function reject(lembur: Lembur) {
    if (confirm(`Tolak lembur ${lembur.karyawan.nama} tanggal ${formatTanggal(lembur.tanggal)}?`)) {
        router.post(`/admin/approval-lembur/${lembur.id_absensi}/reject`, {}, { preserveScroll: true });
    }
}

function formatTanggal(tanggal: string) {
    return new Date(tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusBadgeClass: Record<StatusApproval, string> = {
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
};

const statusLabel: Record<StatusApproval, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};

const previewOpen = ref(false);
const previewUrl = ref('');

function bukaFoto(path: string) {
    previewUrl.value = path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`;
    previewOpen.value = true;
}

const editDialogOpen = ref(false);
const editingLembur = ref<Lembur | null>(null);

function openEdit(lembur: Lembur) {
    editingLembur.value = lembur;
    editDialogOpen.value = true;
}
</script>

<template>
    <Head title="Approval Lembur" />

    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold text-foreground">Approval Lembur</h1>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari karyawan..." class="pl-9" />
            </div>

            <Select v-model="tahunFilter">
                <SelectTrigger class="w-[120px]">
                    <SelectValue placeholder="Tahun" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Tahun</SelectItem>
                    <SelectItem v-for="t in tahunOptions" :key="t" :value="t">{{ t }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="bulanFilter">
                <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Bulan" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Bulan</SelectItem>
                    <SelectItem v-for="(nama, i) in namaBulan" :key="i" :value="String(i + 1)">
                        {{ nama }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="pending">Menunggu</SelectItem>
                    <SelectItem value="approved">Disetujui</SelectItem>
                    <SelectItem value="rejected">Ditolak</SelectItem>
                    <SelectItem value="all">Semua Status</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Karyawan</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Jam Lembur</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                        <th class="px-4 py-3 font-medium">Bukti</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="lemburs.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                            Tidak ada pengajuan lembur.
                        </td>
                    </tr>
                    <tr v-for="l in lemburs.data" :key="l.id_absensi" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ l.karyawan.nama }}</td>
                        <td class="px-4 py-3">{{ formatTanggal(l.tanggal) }}</td>
                        <td class="px-4 py-3">{{ l.jam_lembur }} jam</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ l.keterangan_lembur ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <button
                                v-if="l.foto_pulang"
                                type="button"
                                class="flex items-center gap-1.5 text-primary hover:underline"
                                @click="bukaFoto(l.foto_pulang)"
                            >
                                <Eye class="size-4" />
                                Lihat Foto
                            </button>
                            <span v-else class="flex items-center gap-1.5 text-muted-foreground">
                                <ImageOff class="size-4" />
                                Tidak ada
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusBadgeClass[l.status_approval_lembur]"
                            >
                                {{ statusLabel[l.status_approval_lembur] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(l)">
                                    <Pencil class="size-4" />
                                </Button>
                                <template v-if="l.status_approval_lembur === 'pending'">
                                    <Button size="sm" @click="approve(l)">
                                        <Check class="size-4" />
                                        Approve
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="reject(l)">
                                        <X class="size-4" />
                                    </Button>
                                </template>
                                <span v-else class="self-center text-xs text-muted-foreground">Sudah diproses</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="lemburs.total">
                Menampilkan {{ lemburs.from }}–{{ lemburs.to }} dari {{ lemburs.total }} pengajuan
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in lemburs.links" :key="i">
                    <button
                        v-if="link.url"
                        class="rounded-md px-3 py-1.5 text-xs"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                        v-html="link.label"
                        @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                    />
                    <span v-else class="rounded-md px-3 py-1.5 text-xs text-muted-foreground/50" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>

    <Dialog :open="previewOpen" @update:open="(v) => (previewOpen = v)">
        <DialogContent class="max-w-lg">
            <DialogTitle>Bukti Lembur</DialogTitle>
            <img :src="previewUrl" alt="Bukti lembur" class="w-full rounded-lg object-contain" />
        </DialogContent>
    </Dialog>

    <ApprovalLemburFormDialog v-model:open="editDialogOpen" :lembur="editingLembur" />
</template>