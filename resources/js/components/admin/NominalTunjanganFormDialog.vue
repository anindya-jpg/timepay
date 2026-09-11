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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Jabatan = { id_jabatan: number; nama_jabatan: string };
type Tunjangan = { id_tunjangan: number; nama_tunjangan: string };
type Nominal = {
    id_jabatantunjangan: number;
    id_jabatan: number;
    id_tunjangan: number;
    nominal: number;
};

const props = defineProps<{
    open: boolean;
    nominal?: Nominal | null;
    jabatans: Jabatan[];
    tunjangans: Tunjangan[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    id_jabatan: '',
    id_tunjangan: '',
    nominal: '',
});

const isEdit = () => !!props.nominal;

watch(
    () => [props.open, props.nominal],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.nominal) {
            form.id_jabatan = String(props.nominal.id_jabatan);
            form.id_tunjangan = String(props.nominal.id_tunjangan);
            form.nominal = String(props.nominal.nominal);
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/tunjangan-nominal/${props.nominal!.id_jabatantunjangan}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/tunjangan-nominal', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Nominal' : 'Atur Nominal Tunjangan' }}</DialogTitle>
                <DialogDescription>
                    Tentukan besaran tunjangan untuk jabatan tertentu. Kalau kombinasi jabatan + tunjangan
                    sudah ada, nominalnya akan otomatis diperbarui.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="id_jabatan">Jabatan *</Label>
                    <Select v-model="form.id_jabatan" :disabled="isEdit()">
                        <SelectTrigger id="id_jabatan" class="w-full">
                            <SelectValue placeholder="Pilih jabatan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="j in jabatans" :key="j.id_jabatan" :value="String(j.id_jabatan)">
                                {{ j.nama_jabatan }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.id_jabatan" />
                </div>

                <div class="grid gap-2">
                    <Label for="id_tunjangan">Tunjangan *</Label>
                    <Select v-model="form.id_tunjangan" :disabled="isEdit()">
                        <SelectTrigger id="id_tunjangan" class="w-full">
                            <SelectValue placeholder="Pilih tunjangan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in tunjangans" :key="t.id_tunjangan" :value="String(t.id_tunjangan)">
                                {{ t.nama_tunjangan }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.id_tunjangan" />
                </div>

                <div class="grid gap-2">
                    <Label for="nominal">Nominal (Rp) *</Label>
                    <Input id="nominal" type="number" min="0" step="1000" v-model="form.nominal" />
                    <InputError :message="form.errors.nominal" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>