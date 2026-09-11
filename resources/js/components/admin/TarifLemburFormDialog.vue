<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Tarif = {
    tarif_per_jam: number;
} | null;

const props = defineProps<{
    open: boolean;
    tarif: Tarif;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    tarif_per_jam: '',
});

watch(
    () => props.open,
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.tarif_per_jam = props.tarif ? String(props.tarif.tarif_per_jam) : '';
    },
    { immediate: true },
);

function submit() {
    form.put('/admin/tarif-lembur', {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Update Tarif Lembur</DialogTitle>
                <DialogDescription>
                    Tarif ini dikalikan dengan jumlah jam lembur (yang sudah di-approve) tiap karyawan
                    saat proses generate gaji.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tarif_per_jam">Tarif per Jam (Rp) *</Label>
                    <Input id="tarif_per_jam" type="number" min="0" step="1000" v-model="form.tarif_per_jam" />
                    <InputError :message="form.errors.tarif_per_jam" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">Simpan Perubahan</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>