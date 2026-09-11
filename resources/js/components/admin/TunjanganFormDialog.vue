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

type Tunjangan = {
    id_tunjangan: number;
    nama_tunjangan: string;
    keterangan: string | null;
};

const props = defineProps<{
    open: boolean;
    tunjangan?: Tunjangan | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    nama_tunjangan: '',
    keterangan: '',
});

const isEdit = () => !!props.tunjangan;

watch(
    () => [props.open, props.tunjangan],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.tunjangan) {
            form.nama_tunjangan = props.tunjangan.nama_tunjangan;
            form.keterangan = props.tunjangan.keterangan ?? '';
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/tunjangan/${props.tunjangan!.id_tunjangan}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/tunjangan', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Tunjangan' : 'Tambah Tunjangan' }}</DialogTitle>
                <DialogDescription>
                    Ini master jenis tunjangan. Nominalnya diatur terpisah di tab "Atur Nominal per Jabatan".
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="nama_tunjangan">Nama Tunjangan *</Label>
                    <Input id="nama_tunjangan" v-model="form.nama_tunjangan" placeholder="Contoh: Tunjangan Makan" />
                    <InputError :message="form.errors.nama_tunjangan" />
                </div>

                <div class="grid gap-2">
                    <Label for="keterangan">Keterangan</Label>
                    <textarea
                        id="keterangan"
                        v-model="form.keterangan"
                        rows="2"
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                    />
                    <InputError :message="form.errors.keterangan" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Tunjangan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>