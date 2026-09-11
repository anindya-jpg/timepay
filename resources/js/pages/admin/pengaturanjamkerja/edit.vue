<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PengaturanJamKerjaFormDialog from '@/components/admin/PengaturanJamKerjaFormDialog.vue';
import { Button } from '@/components/ui/button';

type Pengaturan = {
    id_jamkerja: number;
    jam_masuk_standar: string;
    jam_pulang_standar: string;
} | null;

defineProps<{
    pengaturan: Pengaturan;
}>();

const dialogOpen = ref(false);
</script>

<template>
    <Head title="Pengaturan Jam Kerja" />

    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold text-foreground">Pengaturan Jam Kerja</h1>

        <p class="max-w-2xl text-sm text-muted-foreground">
            Jam yang diambil harus lebih longgar  <span class="font-medium text-foreground">1 jam</span> dari jam masuk dan pulang pada peraturan perusahaan,
            karena dalam sistem, kalau absen lewat dari jam ini maka akan otomatis tercatat
            <span class="font-medium text-foreground">telat</span> atau
            <span class="font-medium text-foreground">alpha</span>.
        </p>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Jam Masuk</th>
                        <th class="px-4 py-3 font-medium">Jam Pulang</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="!pengaturan">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada pengaturan jam kerja.
                        </td>
                    </tr>
                    <tr v-else>
                        <td class="px-4 py-3 font-medium text-foreground">
                            {{ pengaturan.jam_masuk_standar.slice(0, 5) }}
                        </td>
                        <td class="px-4 py-3 font-medium text-foreground">
                            {{ pengaturan.jam_pulang_standar.slice(0, 5) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button size="sm" variant="outline" @click="dialogOpen = true">Update</Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <PengaturanJamKerjaFormDialog v-model:open="dialogOpen" :pengaturan="pengaturan" />
</template>