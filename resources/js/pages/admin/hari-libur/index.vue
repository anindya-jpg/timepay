<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import HariLiburFormDialog from '@/components/admin/HariLiburFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type HariLibur = {
    id_libur: number;
    tanggal: string;
    keterangan: string | null;
};

const props = defineProps<{
    hariLiburs: {
        data: HariLibur[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { tahun?: string; bulan?: string; search?: string };
}>();

const search = ref(props.filters.search ?? '');
const tahunFilter = ref(props.filters.tahun ?? 'all');
const bulanFilter = ref(props.filters.bulan ?? 'all');

const tahunOptions = Array.from({ length: 6 }, (_, i) => String(new Date().getFullYear() - 2 + i));
const namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

function applyFilters() {
    router.get(
        '/admin/hari-libur',
        {
            search: search.value || undefined,
            tahun: tahunFilter.value === 'all' ? undefined : tahunFilter.value,
            bulan: bulanFilter.value === 'all' ? undefined : bulanFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});
watch([tahunFilter, bulanFilter], applyFilters);

const dialogOpen = ref(false);
const editingHariLibur = ref<HariLibur | null>(null);

function openCreate() {
    editingHariLibur.value = null;
    dialogOpen.value = true;
}

function openEdit(hariLibur: HariLibur) {
    editingHariLibur.value = hariLibur;
    dialogOpen.value = true;
}

function hapus(hariLibur: HariLibur) {
    if (confirm(`Hapus hari libur tanggal ${formatTanggal(hariLibur.tanggal)}?`)) {
        router.delete(`/admin/hari-libur/${hariLibur.id_libur}`, { preserveScroll: true });
    }
}

function formatTanggal(tanggal: string) {
    return new Date(tanggal).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Hari Libur" />

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Hari Libur</h1>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Hari Libur
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
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

            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari tanggal/keterangan..." class="pl-9" />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="hariLiburs.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data hari libur.
                        </td>
                    </tr>
                    <tr v-for="h in hariLiburs.data" :key="h.id_libur" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ formatTanggal(h.tanggal) }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ h.keterangan ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(h)">Update</Button>
                                <Button size="sm" variant="destructive" @click="hapus(h)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="hariLiburs.total">
                Menampilkan {{ hariLiburs.from }}–{{ hariLiburs.to }} dari {{ hariLiburs.total }} hari libur
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in hariLiburs.links" :key="i">
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

    <HariLiburFormDialog v-model:open="dialogOpen" :hari-libur="editingHariLibur" />
</template>