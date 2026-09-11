<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Camera,
    CheckCircle2,
    Info,
    MapPin,
    RotateCcw,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type HariLibur = {
    tanggal: string;
    keterangan: string;
};

type Absensi = {
    jam_masuk: string | null;
    jam_pulang: string | null;
    foto_masuk: string | null;
    foto_pulang: string | null;
    status: string;
};

type LokasiKantor = {
    nama_lokasi: string;
    latitude: string;
    longitude: string;
    radius_meter: number;
} | null;

const props = defineProps<{
    sudahAbsenMasuk: boolean;
    sudahAbsenPulang: boolean;
    absenHariIni: Absensi | null;
    jamPulangStandar: string | null;
    lokasiKantor: LokasiKantor;
    hariLibur: HariLibur | null;
}>();

/*
|--------------------------------------------------------------------------
| Jam Pulang (tampilan)
|--------------------------------------------------------------------------
| Hanya untuk tampilan di frontend, dibuat 1 jam lebih awal
| dari jam_pulang_standar yang tersimpan di database
| (contoh: database 17:00 -> tampil 16:00).
| Nilai props.jamPulangStandar aslinya TIDAK diubah, jadi
| logic lain yang memakai props.jamPulangStandar tetap
| memakai jam asli dari database.
*/

const jamPulangTampil = computed(() => {
    if (!props.jamPulangStandar) {
        return '-';
    }

    const bagian = props.jamPulangStandar.split(':');

    if (bagian.length < 2) {
        return props.jamPulangStandar;
    }

    let jam = parseInt(bagian[0], 10);
    const menit = bagian[1];

    if (Number.isNaN(jam)) {
        return props.jamPulangStandar;
    }

    jam = jam - 1;

    if (jam < 0) {
        jam = 23;
    }

    const jamString = String(jam).padStart(2, '0');

    return `${jamString}:${menit}`;
});

/*
|--------------------------------------------------------------------------
| Deteksi Kemungkinan Lembur
|--------------------------------------------------------------------------
|
| Dihitung dari jam sekarang (yang terus berjalan tiap detik)
| dibandingkan jamPulangStandar ASLI dari backend (bukan yang
| sudah dimundurkan 1 jam untuk tampilan). Ini cuma prediksi
| di sisi frontend supaya field Keterangan Lembur muncul
| lebih awal; keputusan final tetap dihitung ulang di backend
| saat submit.
*/

const kemungkinanLembur = computed(() => {
    if (!props.jamPulangStandar) {
        return false;
    }

    const bagian = props.jamPulangStandar.split(':');

    if (bagian.length < 2) {
        return false;
    }

    const jamStandar = parseInt(bagian[0], 10);
    const menitStandar = parseInt(bagian[1], 10);

    if (Number.isNaN(jamStandar) || Number.isNaN(menitStandar)) {
        return false;
    }

    const totalMenitStandar = jamStandar * 60 + menitStandar;

    const totalMenitSekarang =
        waktuSekarang.value.getHours() * 60 +
        waktuSekarang.value.getMinutes();

    return totalMenitSekarang > totalMenitStandar;
});

/*
|--------------------------------------------------------------------------
| Waktu
|--------------------------------------------------------------------------
*/

const waktuSekarang = ref(new Date());

let intervalWaktu: ReturnType<typeof setInterval> | null = null;

function formatJam(date: Date) {
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
}

function formatTanggalPanjang(date: Date) {
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

/*
|--------------------------------------------------------------------------
| Kamera
|--------------------------------------------------------------------------
*/

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);

const stream = ref<MediaStream | null>(null);
const fotoCapture = ref<string | null>(null);

const errorKamera = ref('');
const kameraAktif = ref(false);

async function mulaiKamera() {
    try {
        errorKamera.value = '';

        if (
            !navigator.mediaDevices?.getUserMedia
        ) {
            errorKamera.value =
                'Browser kamu tidak mendukung akses kamera.';

            return;
        }

        stream.value =
            await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: {
                        ideal: 1280,
                    },
                    height: {
                        ideal: 720,
                    },
                },
                audio: false,
            });

        if (videoRef.value) {
            videoRef.value.srcObject =
                stream.value;

            kameraAktif.value = true;
        }
    } catch (error) {
        console.error(error);

        errorKamera.value =
            'Tidak bisa akses kamera. Pastikan izin kamera diizinkan di browser, dan situs diakses lewat HTTPS.';

        kameraAktif.value = false;
    }
}

function hentikanKamera() {
    if (stream.value) {
        stream.value
            .getTracks()
            .forEach((track) => {
                track.stop();
            });

        stream.value = null;
    }

    kameraAktif.value = false;
}

/*
|--------------------------------------------------------------------------
| Ambil Foto
|--------------------------------------------------------------------------
*/

function ambilFoto() {
    if (
        !videoRef.value ||
        !canvasRef.value
    ) {
        return;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx =
        canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    /*
     * Balik foto secara horizontal agar hasil foto
     * tidak mirror.
     */

    ctx.save();

    ctx.translate(
        canvas.width,
        0,
    );

    ctx.scale(
        -1,
        1,
    );

    ctx.drawImage(
        video,
        0,
        0,
        canvas.width,
        canvas.height,
    );

    ctx.restore();

    fotoCapture.value =
        canvas.toDataURL(
            'image/jpeg',
            0.9,
        );

    hentikanKamera();
}

function ulangiFoto() {
    fotoCapture.value = null;

    mulaiKamera();
}

/*
|--------------------------------------------------------------------------
| GPS
|--------------------------------------------------------------------------
*/

const koordinat = ref<{
    latitude: number;
    longitude: number;
} | null>(null);

const statusLokasi = ref(
    'Mencari lokasi...',
);

const errorLokasi = ref('');

function ambilLokasi() {
    if (!navigator.geolocation) {
        errorLokasi.value =
            'Browser kamu tidak mendukung GPS.';

        statusLokasi.value =
            'GPS tidak tersedia';

        return;
    }

    statusLokasi.value =
        'Mencari lokasi...';

    errorLokasi.value = '';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            koordinat.value = {
                latitude:
                    position.coords.latitude,

                longitude:
                    position.coords.longitude,
            };

            statusLokasi.value =
                'Lokasi berhasil didapatkan';
        },

        (error) => {
            console.error(error);

            koordinat.value = null;

            statusLokasi.value =
                'Lokasi belum didapatkan';

            errorLokasi.value =
                'Tidak dapat mengambil lokasi. Pastikan GPS dan izin lokasi diaktifkan.';
        },

        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        },
    );
}

/*
|--------------------------------------------------------------------------
| Jarak ke Kantor
|--------------------------------------------------------------------------
*/

function hitungJarakMeter(
    lat1: number,
    lon1: number,
    lat2: number,
    lon2: number,
): number {
    const R = 6371000;

    const toRad = (deg: number) =>
        (deg * Math.PI) / 180;

    const dLat =
        toRad(lat2 - lat1);

    const dLon =
        toRad(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) *
            Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) ** 2;

    const c =
        2 *
        Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a),
        );

    return Math.round(R * c);
}

const jarakMeter = computed(() => {
    if (
        !koordinat.value ||
        !props.lokasiKantor
    ) {
        return null;
    }

    return hitungJarakMeter(
        koordinat.value.latitude,
        koordinat.value.longitude,
        parseFloat(
            props.lokasiKantor.latitude,
        ),
        parseFloat(
            props.lokasiKantor.longitude,
        ),
    );
});

const diLuarRadius = computed(() => {
    if (
        jarakMeter.value === null ||
        !props.lokasiKantor
    ) {
        return false;
    }

    return (
        jarakMeter.value >
        props.lokasiKantor.radius_meter
    );
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    foto: null as File | null,
    latitude: null as number | null,
    longitude: null as number | null,
    keterangan_lembur: '',
});

/*
|--------------------------------------------------------------------------
| Convert Base64 → File
|--------------------------------------------------------------------------
*/

function dataUrlToFile(
    dataUrl: string,
    filename: string,
): File {
    const arr =
        dataUrl.split(',');

    const mimeMatch =
        arr[0].match(
            /:(.*?);/,
        );

    const mime =
        mimeMatch?.[1] ??
        'image/jpeg';

    const binary =
        atob(arr[1]);

    const len =
        binary.length;

    const u8arr =
        new Uint8Array(len);

    for (
        let i = 0;
        i < len;
        i++
    ) {
        u8arr[i] =
            binary.charCodeAt(i);
    }

    return new File(
        [u8arr],
        filename,
        {
            type: mime,
        },
    );
}

/*
|--------------------------------------------------------------------------
| Submit Absen Pulang
|--------------------------------------------------------------------------
*/

function submit() {
    /*
     * Pengaman tambahan.
     * Kalau hari libur, jangan izinkan submit.
     */
    if (props.hariLibur) {
        form.setError(
            'foto',
            'Hari ini adalah hari libur. Absensi tidak tersedia.',
        );

        return;
    }

    if (!fotoCapture.value) {
        form.setError(
            'foto',
            'Silakan ambil foto terlebih dahulu.',
        );

        return;
    }

    if (!koordinat.value) {
        form.setError(
            'foto',
            'Lokasi belum didapatkan. Aktifkan GPS terlebih dahulu.',
        );

        return;
    }

    if (
        kemungkinanLembur.value &&
        !form.keterangan_lembur.trim()
    ) {
        form.setError(
            'keterangan_lembur',
            'Kamu melebihi jam pulang standar. Silakan isi keterangan lembur terlebih dahulu.',
        );

        return;
    }

    form.clearErrors();

    form.foto =
        dataUrlToFile(
            fotoCapture.value,
            `absen-pulang-${Date.now()}.jpg`,
        );

    form.latitude =
        koordinat.value.latitude;

    form.longitude =
        koordinat.value.longitude;

    form.post(
        '/karyawan/absen-pulang',
        {
            forceFormData: true,

            onError: () => {
                // Error dari Laravel otomatis masuk ke form.errors
            },
        },
    );
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    intervalWaktu =
        setInterval(() => {
            waktuSekarang.value =
                new Date();
        }, 1000);

    /*
     * Jangan aktifkan kamera/GPS
     * kalau hari ini hari libur.
     */
    if (!props.hariLibur) {
        mulaiKamera();
        ambilLokasi();
    }
});

onBeforeUnmount(() => {
    hentikanKamera();

    if (intervalWaktu) {
        clearInterval(
            intervalWaktu,
        );
    }
});
</script>

<template>
    <Head title="Absen Pulang" />

    <!-- Hari Libur -->
    <div
        v-if="props.hariLibur"
        class="flex min-h-[70vh] items-center justify-center bg-slate-50 px-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <Info class="h-8 w-8" />
            </div>

            <h2 class="text-lg font-bold text-amber-900">Hari Libur</h2>

            <p class="mt-2 text-sm leading-6 text-amber-800">
                Hari ini merupakan hari libur, sehingga absensi pulang tidak tersedia.
            </p>

            <div class="mt-4 w-full rounded-xl bg-white/60 px-5 py-4">
                <p class="text-xs text-amber-700">Keterangan</p>
                <p class="mt-1 font-semibold text-amber-900">
                    {{ props.hariLibur.keterangan || 'Hari Libur' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Sudah absen pulang -->
    <div
        v-else-if="props.sudahAbsenPulang"
        class="flex min-h-[70vh] items-center justify-center bg-slate-50 px-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-teal-200 bg-teal-50 p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                <CheckCircle2 class="h-8 w-8" />
            </div>

            <h2 class="text-lg font-bold text-teal-900">Absen Pulang Berhasil</h2>

            <p class="mt-2 text-sm text-teal-800">
                Kamu sudah melakukan absen pulang hari ini.
            </p>

            <div
                v-if="props.absenHariIni?.jam_pulang"
                class="mt-4 rounded-xl bg-white/60 px-5 py-3"
            >
                <p class="text-xs text-teal-700">Jam Pulang</p>
                <p class="text-lg font-semibold text-teal-900">
                    {{ props.absenHariIni.jam_pulang }}
                </p>
            </div>
        </div>
    </div>

    <!-- Belum absen masuk -->
    <div
        v-else-if="!props.sudahAbsenMasuk"
        class="flex min-h-[70vh] items-center justify-center bg-slate-50 px-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <AlertCircle class="h-8 w-8" />
            </div>

            <h2 class="text-lg font-bold text-amber-900">Belum Absen Masuk</h2>

            <p class="mt-2 text-sm leading-6 text-amber-800">
                Kamu harus melakukan absen masuk terlebih dahulu sebelum melakukan absen pulang.
            </p>
        </div>
    </div>

    <!-- Halaman Absen Pulang -->
    <div v-else class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-1">
                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                    <span>Dashboard</span>
                    <span>›</span>
                    <span class="text-slate-500">Absen Pulang</span>
                </div>

                <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">
                    Absen Pulang Karyawan
                </h1>

                <p class="text-sm text-slate-500">
                    Silakan ambil foto dan pastikan lokasi kamu sudah terdeteksi.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri: Kamera + Panduan + GPS -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Kamera -->
                    <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="font-bold text-slate-900">Foto Absen Pulang</h2>

                            <div
                                v-if="kameraAktif && !fotoCapture"
                                class="flex items-center gap-1.5 text-xs font-semibold text-red-500"
                            >
                                <span class="h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                                REC
                            </div>
                        </div>

                        <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-slate-900">
                            <!-- Live Camera -->
                            <video
                                v-show="!fotoCapture"
                                ref="videoRef"
                                autoplay
                                playsinline
                                muted
                                class="h-full w-full object-cover opacity-90"
                                style="transform: scaleX(-1)"
                            />

                            <!-- Hasil Foto -->
                            <img
                                v-if="fotoCapture"
                                :src="fotoCapture"
                                alt="Foto absen pulang"
                                class="absolute inset-0 h-full w-full object-cover"
                            />

                            <canvas ref="canvasRef" class="hidden" />

                            <!-- Overlay instruksi + bingkai wajah -->
                            <div
                                v-if="!fotoCapture && !errorKamera"
                                class="pointer-events-none absolute inset-0"
                            >
                                <p class="absolute left-0 right-0 top-3 text-center text-[11px] font-semibold uppercase tracking-wide text-white/90">
                                    Posisikan wajah anda pada kamera
                                </p>

                                
                            </div>

                            <!-- Error Kamera -->
                            <div
                                v-if="errorKamera"
                                class="absolute inset-0 flex items-center justify-center bg-slate-900/90 p-6 text-center"
                            >
                                <div class="space-y-3">
                                    <AlertCircle class="mx-auto h-8 w-8 text-red-400" />

                                    <p class="text-sm text-slate-200">{{ errorKamera }}</p>

                                    <button
                                        type="button"
                                        @click="mulaiKamera"
                                        class="rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/20"
                                    >
                                        Coba Lagi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol shutter, di luar bingkai kamera -->
                        <div class="mt-4 flex justify-center">
                            <button
                                v-if="!fotoCapture"
                                type="button"
                                @click="ambilFoto"
                                :disabled="!kameraAktif"
                                aria-label="Ambil Foto"
                                class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-slate-200 bg-white shadow-md transition hover:border-teal-300 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <span class="h-10 w-10 rounded-full bg-teal-500"></span>
                            </button>

                            <button
                                v-else
                                type="button"
                                @click="ulangiFoto"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Ambil Ulang Foto
                            </button>
                        </div>
                    </div>

                    <!-- Panduan + Lokasi -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                            <div class="flex items-center gap-2 text-slate-900">
                                <Info class="h-4 w-4 text-teal-500" />
                                <h3 class="text-sm font-bold">Panduan Presensi</h3>
                            </div>

                            <ul class="mt-2 space-y-1 text-xs leading-5 text-slate-500">
                                <li>• Pastikan wajah terlihat jelas.</li>
                                <li>• Pastikan pencahayaan cukup.</li>
                                <li>• Pastikan kamu berada di lokasi kerja.</li>
                                <li>• Pastikan GPS sudah terdeteksi.</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                            <div class="flex items-center gap-2 text-slate-900">
                                <MapPin class="h-4 w-4 text-teal-500" />
                                <h3 class="text-sm font-bold">Lokasi</h3>
                            </div>

                            <p class="mt-2 text-xs font-medium text-slate-600">
                                {{ statusLokasi }}
                            </p>

                            <div v-if="koordinat" class="mt-2 space-y-0.5 text-xs text-slate-500">
                                <p>Latitude: {{ koordinat.latitude }}</p>
                                <p>Longitude: {{ koordinat.longitude }}</p>

                                <p v-if="jarakMeter !== null" class="pt-1 text-slate-600">
                                    Jarak dari kantor:
                                    <span
                                        :class="diLuarRadius ? 'font-semibold text-red-500' : 'font-semibold text-teal-600'"
                                    >
                                        {{ jarakMeter }} meter
                                    </span>
                                </p>
                            </div>

                            <p v-if="errorLokasi" class="mt-2 text-xs text-red-500">
                                {{ errorLokasi }}
                            </p>

                            <button
                                v-if="!koordinat"
                                type="button"
                                @click="ambilLokasi"
                                class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                Coba Lagi
                            </button>
                        </div>
                    </div>

                    <!-- Peringatan di luar radius -->
                    <div
                        v-if="diLuarRadius"
                        class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

                        <div>
                            <p class="text-sm font-semibold text-amber-900">
                                Kamu di luar radius kantor
                            </p>

                            <p class="mt-1 text-xs leading-5 text-amber-700">
                                Jarak kamu saat ini sekitar
                                <strong>{{ jarakMeter }} meter</strong>
                                dari {{ props.lokasiKantor?.nama_lokasi ?? 'lokasi kantor' }}.
                                Kamu tetap bisa melanjutkan absen, tapi jarak ini akan tercatat
                                sebagai catatan untuk admin.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Waktu + Detail -->
                <div class="space-y-6">
                    <!-- Waktu Sekarang -->
                    <div class="rounded-2xl bg-slate-900 p-6 text-center shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-400">
                            Waktu Sekarang
                        </p>

                        <p class="mt-2 text-4xl font-bold tracking-wide text-white">
                            {{ formatJam(waktuSekarang) }}
                        </p>

                        <p class="mt-1 text-sm capitalize text-slate-400">
                            {{ formatTanggalPanjang(waktuSekarang) }}
                        </p>
                    </div>

                    <!-- Detail Presensi -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="font-bold text-slate-900">Detail Presensi</h2>

                        <dl class="mt-4 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Status</dt>
                                <dd class="font-semibold text-slate-900">Absen Pulang</dd>
                            </div>

                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Jam Masuk</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ props.absenHariIni?.jam_masuk ?? '-' }}
                                </dd>
                            </div>

                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Jam Pulang Standar</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ jamPulangTampil }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Info waktu pulang -->
                        <div class="mt-4 flex items-start gap-2 rounded-xl bg-teal-50 p-3">
                            <Info class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" />
                            <p class="text-xs leading-5 text-teal-700">
                                Jika melakukan absen sebelum jam pulang standar, waktu pulang
                                cepat akan tercatat pada sistem.
                            </p>
                        </div>

                        <!-- Foto preview -->
                        <div
                            v-if="fotoCapture"
                            class="mt-4 overflow-hidden rounded-xl border border-slate-100"
                        >
                            <img
                                :src="fotoCapture"
                                alt="Preview foto absen pulang"
                                class="aspect-square w-full object-cover"
                            />
                        </div>

                        <!-- Keterangan Lembur -->
                        <div
                            v-if="kemungkinanLembur"
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"
                        >
                            <div class="flex items-center gap-2 text-amber-900">
                                <AlertCircle class="h-4 w-4" />
                                <p class="text-xs font-bold">
                                    Kamu sudah melewati jam pulang standar
                                </p>
                            </div>

                            <label
                                for="keterangan_lembur"
                                class="mt-2 block text-xs font-semibold text-amber-800"
                            >
                                Keterangan Lembur
                            </label>

                            <textarea
                                id="keterangan_lembur"
                                v-model="form.keterangan_lembur"
                                rows="3"
                                placeholder="Jelaskan pekerjaan/alasan lembur kamu..."
                                class="mt-2 w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100"
                            ></textarea>

                            <p
                                v-if="form.errors.keterangan_lembur"
                                class="mt-2 text-xs text-red-600"
                            >
                                {{ form.errors.keterangan_lembur }}
                            </p>
                        </div>

                        <!-- Error -->
                        <div
                            v-if="form.errors.foto"
                            class="mt-4 flex items-start gap-2 rounded-xl bg-red-50 p-3"
                        >
                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                            <p class="text-xs text-red-600">{{ form.errors.foto }}</p>
                        </div>

                        <!-- Submit -->
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <button
                                type="button"
                                @click="submit"
                                :disabled="
                                    form.processing ||
                                    !fotoCapture ||
                                    !koordinat ||
                                    (kemungkinanLembur && !form.keterangan_lembur.trim())
                                "
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <CheckCircle2 class="h-5 w-5" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Absen Pulang' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>