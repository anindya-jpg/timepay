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

type Jabatan = {
    id_jabatan: number;
    nama_jabatan: string;
    gaji_pokok: number;
};

const props = defineProps<{
    open: boolean;
    jabatan?: Jabatan | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    nama_jabatan: '',
    gaji_pokok: '',
});

const isEdit = () => !!props.jabatan;

watch(
    () => [props.open, props.jabatan],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.jabatan) {
            form.nama_jabatan = props.jabatan.nama_jabatan;
            form.gaji_pokok = String(props.jabatan.gaji_pokok);
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/jabatan/${props.jabatan!.id_jabatan}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/jabatan', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Jabatan' : 'Tambah Jabatan' }}</DialogTitle>
                <DialogDescription>Isi nama jabatan dan gaji pokok dasarnya.</DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="nama_jabatan">Nama Jabatan *</Label>
                    <Input id="nama_jabatan" v-model="form.nama_jabatan" placeholder="Contoh: Staff HRD" />
                    <InputError :message="form.errors.nama_jabatan" />
                </div>

                <div class="grid gap-2">
                    <Label for="gaji_pokok">Gaji Pokok *</Label>
                    <Input id="gaji_pokok" type="number" min="0" step="1000" v-model="form.gaji_pokok" />
                    <InputError :message="form.errors.gaji_pokok" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Jabatan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>