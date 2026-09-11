<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Camera,
    CheckCircle2,
    Eye,
    EyeOff,
    Lock,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Save,
    Trash2,
    User,
    WalletCards,
} from '@lucide/vue';
import { ref } from 'vue';

interface Karyawan {
    id_karyawan: number;
    nip: string;
    nama: string;
    jenis_kelamin: string;
    alamat: string | null;
    no_hp: string | null;
    email: string | null;
    no_rekening: string | null;
    username: string;
    foto: string | null;
    tanggal_masuk: string | null;
    status_aktif: string;
    jabatan: string | null;
}

const props = defineProps<{
    karyawan: Karyawan;
}>();

const form = useForm({
    foto: null as File | null,
    email: props.karyawan.email ?? '',
    no_hp: props.karyawan.no_hp ?? '',
    alamat: props.karyawan.alamat ?? '',
    no_rekening: props.karyawan.no_rekening ?? '',
    username: props.karyawan.username ?? '',
    password: '',
    password_confirmation: '',
});

const fotoPreview = ref<string | null>(
    props.karyawan.foto
        ? `/storage/${props.karyawan.foto}`
        : null,
);

const fileInput = ref<HTMLInputElement | null>(null);

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

function pilihFoto() {
    fileInput.value?.click();
}

function handleFoto(event: Event) {
    const target = event.target as HTMLInputElement;

    if (!target.files || !target.files[0]) {
        return;
    }

    const file = target.files[0];

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!allowedTypes.includes(file.type)) {
        alert('Format foto harus JPG, PNG, atau WEBP.');
        target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran foto maksimal 5 MB.');
        target.value = '';
        return;
    }

    form.foto = file;

    if (fotoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(fotoPreview.value);
    }

    fotoPreview.value = URL.createObjectURL(file);
}

function hapusFoto() {
    if (!props.karyawan.foto && !form.foto) {
        return;
    }

    if (!confirm('Yakin ingin menghapus foto profil?')) {
        return;
    }

    form.delete('/karyawan/profil/foto', {
        preserveScroll: true,

        onSuccess: () => {
            form.foto = null;

            if (fotoPreview.value?.startsWith('blob:')) {
                URL.revokeObjectURL(fotoPreview.value);
            }

            fotoPreview.value = null;
        },
    });
}

function submit() {
    form.put('/karyawan/profil', {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            form.reset(
                'password',
                'password_confirmation',
            );

            form.foto = null;
        },
    });
}
</script>

<template>
    <Head title="Profil Saya" />

    <div class="mx-auto w-full max-w-6xl space-y-5">

        <!-- ==================== PAGE HEADER ==================== -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-foreground"
                >
                    Profil Saya
                </h1>

                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola informasi pribadi dan keamanan akun kamu.
                </p>
            </div>

            <Link
                href="/karyawan/dashboard"
                class="inline-flex w-fit items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium transition hover:bg-muted"
            >
                <ArrowLeft class="size-4" />
                Kembali
            </Link>
        </div>

        <!-- ==================== PROFILE ==================== -->
        <section
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <div
                class="flex flex-col items-center gap-5 sm:flex-row"
            >
                <!-- FOTO -->
                <div class="relative h-20 w-20 shrink-0">
                    <div
                        class="h-20 w-20 overflow-hidden rounded-full border-4 border-background bg-primary/10 shadow-md"
                    >
                        <img
                            v-if="fotoPreview"
                            :src="fotoPreview"
                            alt="Foto profil"
                            class="h-full w-full object-cover"
                        />

                        <span
                            v-else
                            class="flex h-full w-full items-center justify-center text-2xl font-bold text-primary"
                        >
                            {{ karyawan.nama.charAt(0).toUpperCase() }}
                        </span>
                    </div>

                    <!-- CAMERA -->
                    <button
                        type="button"
                        @click="pilihFoto"
                        class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full border-4 border-card bg-primary text-primary-foreground shadow-md transition hover:bg-primary/90"
                        title="Ubah foto"
                    >
                        <Camera class="h-3.5 w-3.5" />
                    </button>

                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        @change="handleFoto"
                    />
                </div>

                <!-- IDENTITAS -->
                <div class="min-w-0 flex-1 text-center sm:text-left">
                    <h2
                        class="text-xl font-bold text-foreground"
                    >
                        {{ karyawan.nama }}
                    </h2>

                    <div
                        class="mt-1 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 sm:justify-start"
                    >
                        <span
                            class="text-sm font-medium text-primary"
                        >
                            {{ karyawan.jabatan ?? 'Karyawan' }}
                        </span>

                        <span
                            class="hidden text-muted-foreground sm:inline"
                        >
                            •
                        </span>

                        <span
                            class="text-sm text-muted-foreground"
                        >
                            NIP: {{ karyawan.nip }}
                        </span>
                    </div>

                    <div
                        class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start"
                    >
                        <button
                            type="button"
                            @click="pilihFoto"
                            class="inline-flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium transition hover:bg-muted"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Ganti Foto
                        </button>

                        <button
                            v-if="fotoPreview"
                            type="button"
                            @click="hapusFoto"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Hapus Foto
                        </button>

                        <span
                            class="text-xs text-muted-foreground"
                        >
                            JPG, PNG, WEBP · Maks. 5 MB
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== INFORMASI KARYAWAN ==================== -->
        <section
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <div class="mb-5 flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <User class="size-5" />
                </div>

                <div>
                    <h2 class="font-semibold text-foreground">
                        Informasi Karyawan
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        Data identitas yang dikelola oleh perusahaan.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">

                <!-- NAMA -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Nama Lengkap
                    </label>

                    <input
                        :value="karyawan.nama"
                        disabled
                        class="w-full cursor-not-allowed rounded-lg border border-border bg-muted/50 px-4 py-2.5 text-sm text-muted-foreground"
                    />
                </div>

                <!-- NIP -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        NIP
                    </label>

                    <input
                        :value="karyawan.nip"
                        disabled
                        class="w-full cursor-not-allowed rounded-lg border border-border bg-muted/50 px-4 py-2.5 text-sm text-muted-foreground"
                    />
                </div>

                <!-- JABATAN -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Jabatan
                    </label>

                    <input
                        :value="karyawan.jabatan ?? '-'"
                        disabled
                        class="w-full cursor-not-allowed rounded-lg border border-border bg-muted/50 px-4 py-2.5 text-sm text-muted-foreground"
                    />
                </div>

                <!-- JENIS KELAMIN -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Jenis Kelamin
                    </label>

                    <input
                        :value="
                            karyawan.jenis_kelamin === 'L'
                                ? 'Laki-laki'
                                : 'Perempuan'
                        "
                        disabled
                        class="w-full cursor-not-allowed rounded-lg border border-border bg-muted/50 px-4 py-2.5 text-sm text-muted-foreground"
                    />
                </div>

                <!-- TANGGAL MASUK -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Tanggal Masuk
                    </label>

                    <input
                        :value="karyawan.tanggal_masuk ?? '-'"
                        disabled
                        class="w-full cursor-not-allowed rounded-lg border border-border bg-muted/50 px-4 py-2.5 text-sm text-muted-foreground"
                    />
                </div>

                <!-- STATUS -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Status Karyawan
                    </label>

                    <div
                        class="flex h-[42px] items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 text-sm text-muted-foreground"
                    >
                        <span
                            class="size-2 rounded-full bg-green-500"
                        />

                        {{ karyawan.status_aktif }}
                    </div>
                </div>
            </div>

            <div
                class="mt-4 rounded-lg bg-muted/50 px-4 py-3 text-xs leading-relaxed text-muted-foreground"
            >
                Data seperti nama, NIP, jabatan, jenis kelamin,
                tanggal masuk, dan status hanya dapat diubah oleh admin.
            </div>
        </section>

        <!-- ==================== INFORMASI KONTAK ==================== -->
        <section
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <div class="mb-5 flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Pencil class="size-5" />
                </div>

                <div>
                    <h2 class="font-semibold text-foreground">
                        Informasi yang Bisa Diubah
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        Perbarui informasi kontak dan rekening kamu.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">

                <!-- EMAIL -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Email
                    </label>

                    <div class="relative">
                        <Mail
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />

                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="Masukkan email"
                            class="w-full rounded-lg border border-border bg-background py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            :class="{
                                'border-red-500':
                                    form.errors.email,
                            }"
                        />
                    </div>

                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- NO HP -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Nomor HP
                    </label>

                    <div class="relative">
                        <Phone
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />

                        <input
                            v-model="form.no_hp"
                            type="tel"
                            placeholder="Masukkan nomor HP"
                            class="w-full rounded-lg border border-border bg-background py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            :class="{
                                'border-red-500':
                                    form.errors.no_hp,
                            }"
                        />
                    </div>

                    <p
                        v-if="form.errors.no_hp"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.no_hp }}
                    </p>
                </div>

                <!-- ALAMAT -->
                <div class="md:col-span-2">
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Alamat
                    </label>

                    <div class="relative">
                        <MapPin
                            class="absolute left-3 top-3 size-4 text-muted-foreground"
                        />

                        <textarea
                            v-model="form.alamat"
                            rows="3"
                            placeholder="Masukkan alamat lengkap"
                            class="w-full resize-none rounded-lg border border-border bg-background py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            :class="{
                                'border-red-500':
                                    form.errors.alamat,
                            }"
                        />
                    </div>

                    <p
                        v-if="form.errors.alamat"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.alamat }}
                    </p>
                </div>

                <!-- REKENING -->
                <div class="md:col-span-2">
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Nomor Rekening
                    </label>

                    <div class="relative">
                        <WalletCards
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />

                        <input
                            v-model="form.no_rekening"
                            type="text"
                            placeholder="Masukkan nomor rekening"
                            class="w-full rounded-lg border border-border bg-background py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            :class="{
                                'border-red-500':
                                    form.errors.no_rekening,
                            }"
                        />
                    </div>

                    <p
                        v-if="form.errors.no_rekening"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.no_rekening }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ==================== KEAMANAN AKUN ==================== -->
        <section
            class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6"
        >
            <div class="mb-5 flex items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Lock class="size-5" />
                </div>

                <div>
                    <h2 class="font-semibold text-foreground">
                        Keamanan Akun
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        Kelola username dan password akun kamu.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">

                <!-- USERNAME -->
                <div class="md:col-span-2">
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Username
                    </label>

                    <input
                        v-model="form.username"
                        type="text"
                        placeholder="Masukkan username"
                        class="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                        :class="{
                            'border-red-500':
                                form.errors.username,
                        }"
                    />

                    <p
                        v-if="form.errors.username"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.username }}
                    </p>
                </div>

                <!-- PASSWORD -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Password Baru
                    </label>

                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="
                                showPassword
                                    ? 'text'
                                    : 'password'
                            "
                            placeholder="Kosongkan jika tidak diubah"
                            class="w-full rounded-lg border border-border bg-background py-2.5 pl-4 pr-11 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                            :class="{
                                'border-red-500':
                                    form.errors.password,
                            }"
                        />

                        <button
                            type="button"
                            @click="
                                showPassword =
                                    !showPassword
                            "
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition hover:text-foreground"
                        >
                            <Eye
                                v-if="!showPassword"
                                class="size-4"
                            />

                            <EyeOff
                                v-else
                                class="size-4"
                            />
                        </button>
                    </div>

                    <p
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Minimal 8 karakter.
                    </p>

                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- KONFIRMASI PASSWORD -->
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Konfirmasi Password
                    </label>

                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="
                                showPasswordConfirmation
                                    ? 'text'
                                    : 'password'
                            "
                            placeholder="Ulangi password baru"
                            class="w-full rounded-lg border border-border bg-background py-2.5 pl-4 pr-11 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                        />

                        <button
                            type="button"
                            @click="
                                showPasswordConfirmation =
                                    !showPasswordConfirmation
                            "
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition hover:text-foreground"
                        >
                            <Eye
                                v-if="!showPasswordConfirmation"
                                class="size-4"
                            />

                            <EyeOff
                                v-else
                                class="size-4"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== ERROR ==================== -->
        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            Ada data yang belum benar. Silakan periksa kembali
            bagian yang ditandai.
        </div>

        <!-- ==================== SUCCESS ==================== -->
        <div
            v-if="$page.props.flash?.success"
            class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
        >
            <CheckCircle2 class="size-5 shrink-0" />

            <span>
                {{ $page.props.flash.success }}
            </span>
        </div>

        <!-- ==================== ACTION ==================== -->
        <div
            class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-border bg-card/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:justify-end"
        >
            <Link
                href="/karyawan/dashboard"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-medium transition hover:bg-muted"
            >
                Batal
            </Link>

            <button
                type="button"
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <Save class="size-4" />

                {{
                    form.processing
                        ? 'Menyimpan...'
                        : 'Simpan Perubahan'
                }}
            </button>
        </div>
    </div>
</template>

