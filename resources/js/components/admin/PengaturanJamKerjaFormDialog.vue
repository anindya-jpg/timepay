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

type Pengaturan = {
    jam_masuk_standar: string;
    jam_pulang_standar: string;
} | null;

const props = defineProps<{
    open: boolean;
    pengaturan: Pengaturan;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    jam_masuk_standar: '',
    jam_pulang_standar: '',
});

watch(
    () => props.open,
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.jam_masuk_standar = props.pengaturan?.jam_masuk_standar?.slice(0, 5) ?? '';
        form.jam_pulang_standar = props.pengaturan?.jam_pulang_standar?.slice(0, 5) ?? '';
    },
    { immediate: true },
);

function submit() {
    form.put('/admin/pengaturan-jam-kerja', {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Update Jam Kerja</DialogTitle>
                <DialogDescription>
                    Jam yang diambil sebaiknya lebih longgar 1 jam dari peraturan perusahaan,
                    karena sistem akan mencatat telat/alpha kalau lewat dari jam ini.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="jam_masuk_standar">Jam Masuk *</Label>
                        <Input id="jam_masuk_standar" type="time" v-model="form.jam_masuk_standar" />
                        <InputError :message="form.errors.jam_masuk_standar" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="jam_pulang_standar">Jam Pulang *</Label>
                        <Input id="jam_pulang_standar" type="time" v-model="form.jam_pulang_standar" />
                        <InputError :message="form.errors.jam_pulang_standar" />
                    </div>
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