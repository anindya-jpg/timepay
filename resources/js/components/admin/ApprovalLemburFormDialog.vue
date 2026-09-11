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

type Karyawan = { id_karyawan: number; nama: string; nip: string };
type Lembur = {
    id_absensi: number;
    jam_lembur: number;
    keterangan_lembur: string | null;
    karyawan: Karyawan;
};

const props = defineProps<{
    open: boolean;
    lembur?: Lembur | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    jam_lembur: '',
    keterangan_lembur: '',
});

watch(
    () => [props.open, props.lembur],
    () => {
        if (!props.open || !props.lembur) return;

        form.clearErrors();
        form.jam_lembur = String(props.lembur.jam_lembur);
        form.keterangan_lembur = props.lembur.keterangan_lembur ?? '';
    },
    { immediate: true },
);

function submit() {
    form.put(`/admin/approval-lembur/${props.lembur!.id_absensi}`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Koreksi Jam Lembur</DialogTitle>
                <DialogDescription>
                    {{ lembur?.karyawan.nama }} — perubahan di sini akan otomatis kepakai
                    saat kamu Generate Gaji ulang nanti di menu Penggajian.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="jam_lembur">Jam Lembur *</Label>
                    <Input id="jam_lembur" type="number" min="0" step="0.25" v-model="form.jam_lembur" />
                    <InputError :message="form.errors.jam_lembur" />
                </div>

                <div class="grid gap-2">
                    <Label for="keterangan_lembur">Keterangan</Label>
                    <Input id="keterangan_lembur" v-model="form.keterangan_lembur" />
                    <InputError :message="form.errors.keterangan_lembur" />
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