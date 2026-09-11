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

type AdminAkun = {
    id_admin: number;
    nama: string;
    username: string;
};

const props = defineProps<{
    open: boolean;
    admin?: AdminAkun | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    nama: '',
    username: '',
    password: '',
});

const isEdit = () => !!props.admin;

watch(
    () => [props.open, props.admin],
    () => {
        if (!props.open) return;

        form.clearErrors();
        form.reset();

        if (props.admin) {
            form.nama = props.admin.nama;
            form.username = props.admin.username;
            form.password = '';
        }
    },
    { immediate: true },
);

function submit() {
    if (isEdit()) {
        form.put(`/admin/admin-akun/${props.admin!.id_admin}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin/admin-akun', {
            onSuccess: () => emit('update:open', false),
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Update Admin' : 'Tambah Admin' }}</DialogTitle>
                <DialogDescription>
                    Akun ini bisa dipakai untuk login ke panel Admin TimePay.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="nama">Nama *</Label>
                    <Input id="nama" v-model="form.nama" />
                    <InputError :message="form.errors.nama" />
                </div>

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

                <DialogFooter class="mt-2 gap-2">
                    <Button type="button" variant="secondary" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEdit() ? 'Simpan Perubahan' : 'Tambah Admin' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>