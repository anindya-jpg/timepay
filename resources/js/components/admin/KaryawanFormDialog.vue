<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
type Karyawan = {
    id_karyawan: number;
    nip: string;
    nama: string;
    jenis_kelamin: 'L' | 'P';
    alamat: string | null;
    no_hp: string | null;
    email: string | null;
    no_rekening: string | null;
    username: string;
    id_jabatan: number;
    tanggal_masuk: string | null;
    status_aktif: 'aktif' | 'nonaktif';
};

const props = defineProps<{
    open: boolean;
    karyawan?: Karyawan | null;
    jabatans: Jabatan[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    nip: '',
    nama: '',
    jenis_kelamin: 'L' as 'L' | 'P',
    alamat: '',
    no_hp: '',
    email: '',
    no_rekening: '',
    username: '',
    password: '',
    id_jabatan: '',
    tanggal_masuk: '',
    status_aktif: 'aktif' as 'aktif' | 'nonaktif',
});

const isEdit = () => !!props.karyawan;

watch(
    () => [props.open, props.karyawan],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.karyawan) {
            form.nip = props.karyawan.nip;
            form.nama = props.karyawan.nama;
            form.jenis_kelamin = props.karyawan.jenis_kelamin;
            form.alamat = props.karyawan.alamat ?? '';
            form.no_hp = props.karyawan.no_hp ?? '';
            form.email = props.karyawan.email ?? '';
            form.no_rekening = props.karyawan.no_rekening ?? '';
            form.username = props.karyawan.username;
            form.password = '';
            form.id_jabatan = String(props.karyawan.id_jabatan);
            form.tanggal_masuk = props.karyawan.tanggal_masuk ?? '';
            form.status_aktif = props.karyawan.status_aktif;
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/karyawan/${props.karyawan!.id_karyawan}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/karyawan', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-h-[90vh] max-w-lg overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Karyawan' : 'Tambah Karyawan' }}</DialogTitle>
                <DialogDescription>
                    Lengkapi data di bawah, field bertanda * wajib diisi.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="nip">NIP *</Label>
                        <Input id="nip" v-model="form.nip" />
                        <InputError :message="form.errors.nip" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="nama">Nama *</Label>
                        <Input id="nama" v-model="form.nama" />
                        <InputError :message="form.errors.nama" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="jenis_kelamin">Jenis Kelamin *</Label>
                        <Select v-model="form.jenis_kelamin">
                            <SelectTrigger id="jenis_kelamin" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="L">Laki-laki</SelectItem>
                                <SelectItem value="P">Perempuan</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.jenis_kelamin" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="id_jabatan">Jabatan *</Label>
                        <Select v-model="form.id_jabatan">
                            <SelectTrigger id="id_jabatan" class="w-full">
                                <SelectValue placeholder="Pilih jabatan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="j in jabatans"
                                    :key="j.id_jabatan"
                                    :value="String(j.id_jabatan)"
                                >
                                    {{ j.nama_jabatan }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_jabatan" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="alamat">Alamat</Label>
                    <textarea
                        id="alamat"
                        v-model="form.alamat"
                        rows="2"
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                    />
                    <InputError :message="form.errors.alamat" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="no_hp">No HP</Label>
                        <Input id="no_hp" v-model="form.no_hp" />
                        <InputError :message="form.errors.no_hp" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="form.email" />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="no_rekening">No Rekening</Label>
                        <Input id="no_rekening" v-model="form.no_rekening" />
                        <InputError :message="form.errors.no_rekening" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tanggal_masuk">Tanggal Masuk</Label>
                        <Input id="tanggal_masuk" type="date" v-model="form.tanggal_masuk" />
                        <InputError :message="form.errors.tanggal_masuk" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="username">Username *</Label>
                        <Input id="username" v-model="form.username" autocomplete="off" />
                        <InputError :message="form.errors.username" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">
                            Password {{ isEdit() ? '(kosongkan jika tidak diubah)' : '*' }}
                        </Label>
                        <PasswordInput id="password" v-model="form.password" autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="status_aktif">Status *</Label>
                    <Select v-model="form.status_aktif">
                        <SelectTrigger id="status_aktif" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="aktif">Aktif</SelectItem>
                            <SelectItem value="nonaktif">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status_aktif" />
                </div>

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Karyawan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>