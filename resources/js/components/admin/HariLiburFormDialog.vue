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

type HariLibur = {
    id_libur: number;
    tanggal: string;
    keterangan: string | null;
};

const props = defineProps<{
    open: boolean;
    hariLibur?: HariLibur | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    tanggal: '',
    keterangan: '',
});

const isEdit = () => !!props.hariLibur;

watch(
    () => [props.open, props.hariLibur],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.hariLibur) {
            form.tanggal = props.hariLibur.tanggal.slice(0, 10);
            form.keterangan = props.hariLibur.keterangan ?? '';
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/hari-libur/${props.hariLibur!.id_libur}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/hari-libur', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Hari Libur' : 'Tambah Hari Libur' }}</DialogTitle>
                <DialogDescription>
                    Tanggal yang ditandai di sini tidak akan dihitung sebagai hari kerja/alpha.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="tanggal">Tanggal *</Label>
                    <Input id="tanggal" type="date" v-model="form.tanggal" />
                    <InputError :message="form.errors.tanggal" />
                </div>

                <div class="grid gap-2">
                    <Label for="keterangan">Keterangan</Label>
                    <Input id="keterangan" v-model="form.keterangan" placeholder="Contoh: Hari Kemerdekaan RI" />
                    <InputError :message="form.errors.keterangan" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Hari Libur' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>