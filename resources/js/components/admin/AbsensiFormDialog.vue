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

type Karyawan = { id_karyawan: number; nama: string; nip: string };
type Status = 'Hadir' | 'Telat' | 'Pulang Cepat' | 'Alpha' | 'Sakit' | 'Izin';
type Absensi = {
    id_absensi: number;
    id_karyawan: number;
    tanggal: string;
    jam_masuk: string | null;
    jam_pulang: string | null;
    status: Status;
    menit_telat: number;
    karyawan: Karyawan;
};

const props = defineProps<{
    open: boolean;
    absensi?: Absensi | null;
    karyawans: Karyawan[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const statusOptions: Status[] = ['Hadir', 'Telat', 'Pulang Cepat', 'Alpha', 'Sakit', 'Izin'];

const form = useForm({
    id_karyawan: '',
    tanggal: '',
    jam_masuk: '',
    jam_pulang: '',
    status: 'Hadir' as Status,
    menit_telat: '0',
});

const isEdit = () => !!props.absensi;

watch(
    () => [props.open, props.absensi],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.absensi) {
            form.id_karyawan = String(props.absensi.id_karyawan);
            form.tanggal = props.absensi.tanggal.slice(0, 10);
            form.jam_masuk = props.absensi.jam_masuk?.slice(0, 5) ?? '';
            form.jam_pulang = props.absensi.jam_pulang?.slice(0, 5) ?? '';
            form.status = props.absensi.status;
            form.menit_telat = String(props.absensi.menit_telat);
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/absensi/${props.absensi!.id_absensi}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/absensi', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Koreksi Absensi' : 'Input Absensi Manual' }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEdit()
                            ? 'Perbaiki data absensi kalau ada kesalahan pencatatan.'
                            : 'Dipakai kalau karyawan lupa absen atau perlu input manual.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="id_karyawan">Karyawan *</Label>
                    <Select v-model="form.id_karyawan" :disabled="isEdit()">
                        <SelectTrigger id="id_karyawan" class="w-full">
                            <SelectValue placeholder="Pilih karyawan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="k in karyawans"
                                :key="k.id_karyawan"
                                :value="String(k.id_karyawan)"
                            >
                                {{ k.nama }} ({{ k.nip }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.id_karyawan" />
                </div>

                <div class="grid gap-2">
                    <Label for="tanggal">Tanggal *</Label>
                    <Input id="tanggal" type="date" v-model="form.tanggal" :disabled="isEdit()" />
                    <InputError :message="form.errors.tanggal" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="jam_masuk">Jam Masuk</Label>
                        <Input id="jam_masuk" type="time" v-model="form.jam_masuk" />
                        <InputError :message="form.errors.jam_masuk" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="jam_pulang">Jam Pulang</Label>
                        <Input id="jam_pulang" type="time" v-model="form.jam_pulang" />
                        <InputError :message="form.errors.jam_pulang" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="status">Status *</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="status" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in statusOptions" :key="s" :value="s">{{ s }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="menit_telat">Menit Telat</Label>
                        <Input id="menit_telat" type="number" min="0" v-model="form.menit_telat" />
                        <InputError :message="form.errors.menit_telat" />
                    </div>
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Absensi' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>