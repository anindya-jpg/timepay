<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import TunjanganFormDialog from '@/components/admin/TunjanganFormDialog.vue';
import TunjanganTabs from '@/components/admin/TunjanganTabs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Tunjangan = {
    id_tunjangan: number;
    nama_tunjangan: string;
    keterangan: string | null;
};

const props = defineProps<{
    tunjangans: {
        data: Tunjangan[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/admin/tunjangan', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

const dialogOpen = ref(false);
const editingTunjangan = ref<Tunjangan | null>(null);

function openCreate() {
    editingTunjangan.value = null;
    dialogOpen.value = true;
}

function openEdit(tunjangan: Tunjangan) {
    editingTunjangan.value = tunjangan;
    dialogOpen.value = true;
}

function hapus(tunjangan: Tunjangan) {
    if (confirm(`Hapus tunjangan "${tunjangan.nama_tunjangan}"? Nominal per jabatan yang terkait juga akan ikut terhapus.`)) {
        router.delete(`/admin/tunjangan/${tunjangan.id_tunjangan}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Data Tunjangan" />

    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Data Tunjangan</h1>
            <TunjanganTabs active="daftar" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari nama tunjangan..." class="pl-9" />
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tunjangan
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Tunjangan</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="tunjangans.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data tunjangan.
                        </td>
                    </tr>
                    <tr v-for="t in tunjangans.data" :key="t.id_tunjangan" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ t.nama_tunjangan }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ t.keterangan ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(t)">Update</Button>
                                <Button size="sm" variant="destructive" @click="hapus(t)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="tunjangans.total">
                Menampilkan {{ tunjangans.from }}–{{ tunjangans.to }} dari {{ tunjangans.total }} tunjangan
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in tunjangans.links" :key="i">
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

    <TunjanganFormDialog v-model:open="dialogOpen" :tunjangan="editingTunjangan" />
</template>