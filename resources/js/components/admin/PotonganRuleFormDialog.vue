<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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

type Jenis = 'per_menit' | 'per_hari' | 'nominal_tetap' | 'persen';

type PotonganRule = {
    id_potongan: number;
    nama_potongan: string;
    jenis: Jenis;
    nominal: number;
};

const props = defineProps<{
    open: boolean;
    potongan?: PotonganRule | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const jenisOptions: { value: Jenis; label: string }[] = [
    { value: 'per_menit', label: 'Per Menit (telat)' },
    { value: 'per_hari', label: 'Per Hari' },
    { value: 'nominal_tetap', label: 'Nominal Tetap' },
    { value: 'persen', label: 'Persen (dari gaji pokok)' },
];

const form = useForm({
    nama_potongan: '',
    jenis: 'nominal_tetap' as Jenis,
    nominal: '',
});

const isEdit = () => !!props.potongan;

const nominalSuffix = computed(() => (form.jenis === 'persen' ? '%' : 'Rp'));

watch(
    () => [props.open, props.potongan],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.potongan) {
            form.nama_potongan = props.potongan.nama_potongan;
            form.jenis = props.potongan.jenis;
            form.nominal = String(props.potongan.nominal);
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/potongan-rule/${props.potongan!.id_potongan}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/potongan-rule', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Potongan' : 'Tambah Potongan' }}</DialogTitle>
                <DialogDescription>
                    Aturan ini dipakai otomatis saat generate gaji, berdasarkan status absensi karyawan.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="nama_potongan">Nama Potongan *</Label>
                    <Input id="nama_potongan" v-model="form.nama_potongan" placeholder="Contoh: Telat" />
                    <InputError :message="form.errors.nama_potongan" />
                </div>

                <div class="grid gap-2">
                    <Label for="jenis">Jenis *</Label>
                    <Select v-model="form.jenis">
                        <SelectTrigger id="jenis" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="j in jenisOptions" :key="j.value" :value="j.value">
                                {{ j.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.jenis" />
                </div>

                <div class="grid gap-2">
                    <Label for="nominal">Nominal ({{ nominalSuffix }}) *</Label>
                    <Input id="nominal" type="number" min="0" step="0.01" v-model="form.nominal" />
                    <p class="text-xs text-muted-foreground">
                        <span v-if="form.jenis === 'per_menit'">Dikalikan jumlah menit telat.</span>
                        <span v-else-if="form.jenis === 'per_hari'">Dikalikan jumlah hari (misal sakit).</span>
                        <span v-else-if="form.jenis === 'persen'">Persen dari gaji pokok.</span>
                        <span v-else>Nominal tetap langsung, tidak dikalikan apapun.</span>
                    </p>
                    <InputError :message="form.errors.nominal" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Potongan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>