<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import TarifLemburFormDialog from '@/components/admin/TarifLemburFormDialog.vue';
import { Button } from '@/components/ui/button';

type Tarif = {
    id_tarif: number;
    tarif_per_jam: number;
} | null;

defineProps<{
    tarif: Tarif;
}>();

const dialogOpen = ref(false);

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}
</script>

<template>
    <Head title="Tarif Lembur" />

    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold text-foreground">Tarif Lembur</h1>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Tarif Lembur per Jam</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="!tarif">
                        <td colspan="2" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada tarif lembur.
                        </td>
                    </tr>
                    <tr v-else>
                        <td class="px-4 py-3 font-medium text-foreground">{{ formatRupiah(tarif.tarif_per_jam) }}</td>
                        <td class="px-4 py-3 text-right">
                            <Button size="sm" variant="outline" @click="dialogOpen = true">Update</Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <TarifLemburFormDialog v-model:open="dialogOpen" :tarif="tarif" />
</template>