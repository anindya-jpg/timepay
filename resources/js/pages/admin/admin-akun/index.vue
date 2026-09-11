<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import AdminAkunFormDialog from '@/components/admin/AdminAkunFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type AdminAkun = {
    id_admin: number;
    nama: string;
    username: string;
};

const props = defineProps<{
    admins: {
        data: AdminAkun[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string };
}>();

const page = usePage();
const currentAdminId = (page.props.auth?.admin as { id_admin?: number } | null)?.id_admin;

const search = ref(props.filters.search ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/admin/admin-akun', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

const dialogOpen = ref(false);
const editingAdmin = ref<AdminAkun | null>(null);

function openCreate() {
    editingAdmin.value = null;
    dialogOpen.value = true;
}

function openEdit(admin: AdminAkun) {
    editingAdmin.value = admin;
    dialogOpen.value = true;
}

function hapus(admin: AdminAkun) {
    if (admin.id_admin === currentAdminId) {
        alert('Tidak bisa menghapus akun sendiri yang sedang login.');
        return;
    }
    if (confirm(`Hapus akun admin "${admin.nama}"?`)) {
        router.delete(`/admin/admin-akun/${admin.id_admin}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Data Admin" />

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-foreground">Data Admin</h1>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Admin
            </Button>
        </div>

        <div class="relative w-full sm:max-w-xs">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Cari nama atau username..." class="pl-9" />
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Username</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="admins.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada data admin.
                        </td>
                    </tr>
                    <tr v-for="a in admins.data" :key="a.id_admin" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium text-foreground">
                            {{ a.nama }}
                            <span v-if="a.id_admin === currentAdminId" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                Kamu
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ a.username }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" @click="openEdit(a)">Update</Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    :disabled="a.id_admin === currentAdminId"
                                    @click="hapus(a)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span v-if="admins.total">
                Menampilkan {{ admins.from }}–{{ admins.to }} dari {{ admins.total }} admin
            </span>
            <div class="flex gap-1">
                <template v-for="(link, i) in admins.links" :key="i">
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

    <AdminAkunFormDialog v-model:open="dialogOpen" :admin="editingAdmin" />
</template>