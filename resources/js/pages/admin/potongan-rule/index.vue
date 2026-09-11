<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import PotonganRuleFormDialog from '@/components/admin/PotonganRuleFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Jenis = 'per_menit' | 'per_hari' | 'nominal_tetap' | 'persen';

type PotonganRule = {
    id_potongan: number;
    nama_potongan: string;
    jenis: Jenis;
    nominal: number;
};

const props = defineProps<{
    potongans: {
        data: PotonganRule[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; jenis?: string };
}>();

const jenisLabel: Record<Jenis, string> = {
    per_menit: 'Per Menit',
    per_hari: 'Per Hari',
    nominal_tetap: 'Nominal',
    persen: 'Persen',
};

const search = ref(props.filters.search ?? '');
const jenisFilter = ref(props.filters.jenis ?? 'all');

function applyFilters() {
    router.get(
        '/admin/potongan-rule',
        {
            search: search.value || undefined,
            jenis: jenisFilter.value === 'all' ? undefined : jenisFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});
watch(jenisFilter, applyFilters);

const dialogOpen = ref(false);
const editingPotongan = ref<PotonganRule | null>(null);

function openCreate() {
    editingPotongan.value = null;
    dialogOpen.value = true;
}

function openEdit(potongan: PotonganRule) {
    editingPotongan.value = potongan;
    dialogOpen.value = true;
}

function hapus(potongan: PotonganRule) {
    if (confirm(`Hapus aturan potongan "${potongan.nama_potongan}"?`)) {
        router.delete(`/admin/potongan-rule/${potongan.id_potongan}`, { preserveScroll: true });
    }
}

function formatNominal(potongan: PotonganRule) {
    if (potongan.jenis === 'persen') {
        return `${potongan.nominal}%`;
    }
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(
        potongan.nominal,
    );
}
</script>

<template>
    <Head title="Aturan Potongan" />

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Aturan Potongan</h1>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Potongan
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Cari nama potongan..." class="pl-9" />
            </div>

            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Filter:</span>
                <Select v-model="jenisFilter">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="Jenis" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Jenis</SelectItem>
                        <SelectItem value="per_menit">Per Menit</SelectItem>
                        <SelectItem value="per_hari">Per Hari</SelectItem>
                        <SelectItem value="nominal_tetap">Nominal</SelectItem>
                        <SelectItem value="persen">Persen</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama Potongan</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Nominal</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="potongans.data.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada aturan potongan.
                        </td>
                    </tr>
                    <tr v-for="p in potongans.data" :key="p.id_potongan" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">{{ p.nama_potongan }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {{ jenisLabel[p.jenis] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ formatNominal(p) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(p)">Update</Button>
                                <Button size="sm" variant="destructive" @click="hapus(p)">Delete</Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="potongans.total">
                Menampilkan {{ potongans.from }}–{{ potongans.to }} dari {{ potongans.total }} aturan
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in potongans.links" :key="i">
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

    <PotonganRuleFormDialog v-model:open="dialogOpen" :potongan="editingPotongan" />
</template>