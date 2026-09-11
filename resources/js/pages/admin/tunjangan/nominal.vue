<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import NominalTunjanganFormDialog from '@/components/admin/NominalTunjanganFormDialog.vue';
import TunjanganTabs from '@/components/admin/TunjanganTabs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Jabatan = { id_jabatan: number; nama_jabatan: string };
type Tunjangan = { id_tunjangan: number; nama_tunjangan: string };
type Nominal = {
    id_jabatantunjangan: number;
    id_jabatan: number;
    id_tunjangan: number;
    nominal: number;
    jabatan: Jabatan;
    tunjangan: Tunjangan;
};

const props = defineProps<{
    nominals: {
        data: Nominal[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    jabatans: Jabatan[];
    tunjangans: Tunjangan[];
    filters: { search?: string; id_jabatan?: string };
}>();

const search = ref(props.filters.search ?? '');
const jabatanFilter = ref(props.filters.id_jabatan ?? 'all');

function applyFilters() {
    router.get(
        '/admin/tunjangan-nominal',
        {
            search: search.value || undefined,
            id_jabatan: jabatanFilter.value === 'all' ? undefined : jabatanFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});
watch(jabatanFilter, applyFilters);

const dialogOpen = ref(false);
const editingNominal = ref<Nominal | null>(null);

function openCreate() {
    editingNominal.value = null;
    dialogOpen.value = true;
}

function openEdit(nominal: Nominal) {
    editingNominal.value = nominal;
    dialogOpen.value = true;
}

function hapus(nominal: Nominal) {
    if (confirm(`Hapus nominal "${nominal.tunjangan.nama_tunjangan}" untuk jabatan "${nominal.jabatan.nama_jabatan}"?`)) {
        router.delete(`/admin/tunjangan-nominal/${nominal.id_jabatantunjangan}`, { preserveScroll: true });
    }
}

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
</script>

<template>
    <Head title="Atur Nominal Tunjangan" />

    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Data Tunjangan</h1>
            <TunjanganTabs active="nominal" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari tunjangan..." class="pl-9" />
                </div>
                <Select v-model="jabatanFilter">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Jabatan" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Jabatan</SelectItem>
                        <SelectItem v-for="j in jabatans" :key="j.id_jabatan" :value="String(j.id_jabatan)">
                            {{ j.nama_jabatan }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Atur Tunjangan
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Jabatan</th>
                        <th class="px-4 py-3 font-medium">Tunjangan</th>
                        <th class="px-4 py-3 font-medium">Nominal</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="nominals.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada nominal tunjangan yang diatur.
                        </td>
                    </tr>
                    <tr v-for="n in nominals.data" :key="n.id_jabatantunjangan" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ n.jabatan.nama_jabatan }}</td>
                        <td class="px-4 py-3">{{ n.tunjangan.nama_tunjangan }}</td>
                        <td class="px-4 py-3">{{ formatRupiah(n.nominal) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(n)">Update</Button>
                                <Button size="sm" variant="destructive" @click="hapus(n)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="nominals.total">
                Menampilkan {{ nominals.from }}–{{ nominals.to }} dari {{ nominals.total }} data
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in nominals.links" :key="i">
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

    <NominalTunjanganFormDialog
        v-model:open="dialogOpen"
        :nominal="editingNominal"
        :jabatans="jabatans"
        :tunjangans="tunjangans"
    />
</template> 