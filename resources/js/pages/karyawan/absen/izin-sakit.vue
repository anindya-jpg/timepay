<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    FileText,
    Info,
    Upload,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type HariLibur = {
    tanggal: string;
    keterangan: string;
};

type Absensi = {
    status: string;
    keterangan: string | null;
};

const props = defineProps<{
    hariLibur: HariLibur | null;
    sudahAdaCatatanHariIni: boolean;
    absenHariIni: Absensi | null;
}>();

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    jenis: '' as '' | 'Sakit' | 'Izin',
    keterangan: '',
    surat: null as File | null,
});

const suratPreviewNama = ref<string | null>(null);

function pilihJenis(jenis: 'Sakit' | 'Izin') {
    form.jenis = jenis;

    /*
     * File surat yang sudah dipilih TIDAK dihapus lagi
     * ketika pindah jenis, karena upload sekarang berlaku
     * untuk Sakit maupun Izin.
     */
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;

    const file = target.files?.[0] ?? null;

    form.surat = file;
    suratPreviewNama.value = file?.name ?? null;
}

function hapusFile() {
    form.surat = null;
    suratPreviewNama.value = null;
}

const bisaKirim = computed(() => {
    if (!form.jenis) {
        return false;
    }

    if (!form.keterangan.trim()) {
        return false;
    }

    return true;
});

function submit() {
    form.post('/karyawan/izin-sakit', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Izin / Sakit" />

    <div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <!-- Header / Breadcrumb -->
            <div class="mb-6 flex flex-col gap-1">
                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                    <Link href="/karyawan/dashboard" class="transition hover:text-slate-600">
                        Dashboard
                    </Link>
                    <span>›</span>
                    <span class="text-slate-500">Izin / Sakit</span>
                </div>

                <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">
                    Pengajuan Izin / Sakit
                </h1>
            </div>

            <!-- Hari Libur -->
            <div
                v-if="props.hariLibur"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
                    >
                        <Info class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-amber-900">
                            Hari Libur
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-amber-800">
                            Hari ini adalah
                            <strong>{{ props.hariLibur.keterangan }}</strong>.
                        </p>

                        <p class="mt-2 text-sm text-amber-700">
                            Pengajuan izin/sakit tidak tersedia pada hari libur.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sudah Ada Catatan Hari Ini -->
            <div
                v-else-if="props.sudahAdaCatatanHariIni"
                class="rounded-2xl border border-teal-200 bg-teal-50 p-6 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-600"
                    >
                        <CheckCircle2 class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-teal-900">
                            Sudah Ada Catatan Hari Ini
                        </h2>

                        <p class="mt-1 text-sm text-teal-800">
                            Kamu sudah memiliki catatan absensi untuk hari ini.
                        </p>

                        <div
                            v-if="props.absenHariIni"
                            class="mt-3 space-y-1 text-sm text-teal-800"
                        >
                            <p>
                                Status:
                                <strong>{{ props.absenHariIni.status }}</strong>
                            </p>

                            <p v-if="props.absenHariIni.keterangan">
                                Keterangan:
                                <strong>{{ props.absenHariIni.keterangan }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Pengajuan -->
            <div
                v-else
                class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 sm:p-6"
            >
                <!-- Pilih Jenis -->
                <div>
                    <label class="text-sm font-bold text-slate-900">
                        Jenis Pengajuan
                    </label>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="pilihJenis('Sakit')"
                            class="rounded-xl border-2 px-4 py-3 text-sm font-semibold transition"
                            :class="
                                form.jenis === 'Sakit'
                                    ? 'border-teal-500 bg-teal-50 text-teal-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                            "
                        >
                            Sakit
                        </button>

                        <button
                            type="button"
                            @click="pilihJenis('Izin')"
                            class="rounded-xl border-2 px-4 py-3 text-sm font-semibold transition"
                            :class="
                                form.jenis === 'Izin'
                                    ? 'border-teal-500 bg-teal-50 text-teal-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                            "
                        >
                            Izin
                        </button>
                    </div>

                    <p v-if="form.errors.jenis" class="mt-2 text-xs text-red-600">
                        {{ form.errors.jenis }}
                    </p>
                </div>

                <!-- Keterangan -->
                <div class="mt-5">
                    <label for="keterangan" class="text-sm font-bold text-slate-900">
                        Keterangan
                    </label>

                    <textarea
                        id="keterangan"
                        v-model="form.keterangan"
                        rows="4"
                        placeholder="Jelaskan alasan izin/sakit kamu..."
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100"
                    ></textarea>

                    <p v-if="form.errors.keterangan" class="mt-2 text-xs text-red-600">
                        {{ form.errors.keterangan }}
                    </p>
                </div>

                <!-- Upload Surat / Lampiran Pendukung (Sakit maupun Izin) -->
                <div v-if="form.jenis" class="mt-5">
                    <label class="text-sm font-bold text-slate-900">
                        {{ form.jenis === 'Sakit' ? 'Surat Dokter' : 'Surat / Lampiran Pendukung' }}
                        <span class="font-normal text-slate-400">(opsional)</span>
                    </label>

                    <label
                        class="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500 transition hover:border-teal-300 hover:bg-teal-50/50"
                    >
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="hidden"
                            @change="onFileChange"
                        />

                        <template v-if="suratPreviewNama">
                            <FileText class="h-4 w-4 text-teal-500" />
                            <span class="font-medium text-teal-700">{{ suratPreviewNama }}</span>
                        </template>

                        <template v-else>
                            <Upload class="h-4 w-4" />
                            <span>Klik untuk unggah surat/keterangan (JPG, PNG, atau PDF)</span>
                        </template>
                    </label>

                    <button
                        v-if="suratPreviewNama"
                        type="button"
                        @click="hapusFile"
                        class="mt-2 text-xs font-medium text-red-500 hover:underline"
                    >
                        Hapus file
                    </button>

                    <p v-if="form.errors.surat" class="mt-2 text-xs text-red-600">
                        {{ form.errors.surat }}
                    </p>
                </div>

                <!-- Info -->
                <div class="mt-5 flex items-start gap-2 rounded-xl bg-slate-50 p-3">
                    <Info class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                    <p class="text-xs leading-5 text-slate-500">
                        Pengajuan ini hanya berlaku untuk hari ini dan akan langsung
                        tercatat pada riwayat absensi kamu.
                    </p>
                </div>

                <!-- Submit -->
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="!bisaKirim || form.processing"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <CheckCircle2 class="h-5 w-5" />
                        {{ form.processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
                    </button>

                    <p v-if="!form.jenis" class="mt-3 text-center text-xs text-slate-400">
                        Pilih jenis pengajuan terlebih dahulu.
                    </p>

                    <p
                        v-else-if="!form.keterangan.trim()"
                        class="mt-3 text-center text-xs text-slate-400"
                    >
                        Isi keterangan terlebih dahulu.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>