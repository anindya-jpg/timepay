<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import JabatanFormDialog from '@/components/admin/JabatanFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Jabatan = {
    id_jabatan: number;
    nama_jabatan: string;
    gaji_pokok: number;
    karyawans_count: number;
};

const props = defineProps<{
    jabatans: {
        data: Jabatan[];
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
        router.get(
            '/admin/jabatan',
            { search: search.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 400);
});

const dialogOpen = ref(false);
const editingJabatan = ref<Jabatan | null>(null);

function openCreate() {
    editingJabatan.value = null;
    dialogOpen.value = true;
}

function openEdit(jabatan: Jabatan) {
    editingJabatan.value = jabatan;
    dialogOpen.value = true;
}

function hapus(jabatan: Jabatan) {
    if (jabatan.karyawans_count > 0) {
        alert(`Jabatan "${jabatan.nama_jabatan}" masih dipakai ${jabatan.karyawans_count} karyawan, tidak bisa dihapus.`);
        return;
    }
    if (confirm(`Hapus jabatan "${jabatan.nama_jabatan}"?`)) {
        router.delete(`/admin/jabatan/${jabatan.id_jabatan}`, { preserveScroll: true });
    }
}

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
</script>

<template>
    <Head title="Data Jabatan" />

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Data Jabatan</h1>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Jabatan
            </Button>
        </div>

        <div class="relative w-full sm:max-w-xs">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Cari nama jabatan..." class="pl-9" />
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Jabatan</th>
                        <th class="px-4 py-3 font-medium">Gaji Pokok</th>
                        <th class="px-4 py-3 font-medium">Jumlah Karyawan</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="jabatans.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data jabatan.
                        </td>
                    </tr>
                    <tr v-for="j in jabatans.data" :key="j.id_jabatan" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ j.nama_jabatan }}</td>
                        <td class="px-4 py-3">{{ formatRupiah(j.gaji_pokok) }}</td>
                        <td class="px-4 py-3">{{ j.karyawans_count }} orang</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(j)">Update</Button>
                                <Button size="sm" variant="destructive" @click="hapus(j)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="jabatans.total">
                Menampilkan {{ jabatans.from }}–{{ jabatans.to }} dari {{ jabatans.total }} jabatan
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in jabatans.links" :key="i">
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

    <JabatanFormDialog v-model:open="dialogOpen" :jabatan="editingJabatan" />
</template>