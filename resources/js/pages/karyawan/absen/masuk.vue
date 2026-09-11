<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Camera,
    CheckCircle2,
    Info,
    MapPin,
    RotateCcw,
} from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

type HariLibur = {
    tanggal: string;
    keterangan: string;
};

type Absensi = {
    jam_masuk: string | null;
    foto_masuk: string | null;
    status: string;
};

type Karyawan = {
    nama: string;
};

type PageProps = {
    auth?: {
        karyawan?: Karyawan | null;
    };
};

type LokasiKantor = {
    nama_lokasi: string;
    latitude: string;
    longitude: string;
    radius_meter: number;
} | null;

const props = defineProps<{
    sudahAbsenMasuk: boolean;
    absenHariIni: Absensi | null;
    jamMasukStandar: string | null;
    lokasiKantor: LokasiKantor;
    hariLibur: HariLibur | null;
    batasWaktuTerlewat: boolean;
    batasAbsenMasuk: string | null;
}>();

const page = usePage<PageProps>();

const namaKaryawan = computed(() => {
    return page.props.auth?.karyawan?.nama ?? 'Karyawan';
});

/*
|--------------------------------------------------------------------------
| Jam Masuk (tampilan)
|--------------------------------------------------------------------------
| Hanya untuk tampilan di frontend, dibuat 1 jam lebih awal
| dari jam_masuk_standar yang tersimpan di database
| (contoh: database 09:00 -> tampil 08:00).
| Nilai props.jamMasukStandar aslinya TIDAK diubah, jadi
| validasi/logic lain yang memakai props.jamMasukStandar
| tetap memakai jam asli dari database.
*/

const jamMasukTampil = computed(() => {
    if (!props.jamMasukStandar) {
        return '-';
    }

    const bagian = props.jamMasukStandar.split(':');

    if (bagian.length < 2) {
        return props.jamMasukStandar;
    }

    let jam = parseInt(bagian[0], 10);
    const menit = bagian[1];

    if (Number.isNaN(jam)) {
        return props.jamMasukStandar;
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
| Waktu
|--------------------------------------------------------------------------
*/

const waktuSekarang = ref(new Date());

let clockInterval: ReturnType<typeof setInterval> | null = null;

const tanggalSekarang = computed(() => {
    return waktuSekarang.value.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
});

const jamSekarang = computed(() => {
    return waktuSekarang.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

/*
|--------------------------------------------------------------------------
| Kamera
|--------------------------------------------------------------------------
*/

const video = ref<HTMLVideoElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);

const stream = ref<MediaStream | null>(null);

const kameraAktif = ref(false);
const kameraError = ref('');

const fotoPreview = ref<string | null>(null);
const fotoFile = ref<File | null>(null);

/*
|--------------------------------------------------------------------------
| GPS
|--------------------------------------------------------------------------
*/

const latitude = ref<number | null>(null);
const longitude = ref<number | null>(null);

const gpsAktif = ref(false);
const gpsError = ref('');

const jarakMeter = ref<number | null>(null);
const diLuarRadius = ref(false);

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    foto: null as File | null,
    latitude: null as number | null,
    longitude: null as number | null,
});

/*
|--------------------------------------------------------------------------
| Lokasi
|--------------------------------------------------------------------------
*/

const lokasiTersedia = computed(() => {
    return props.lokasiKantor !== null;
});

const radiusKantor = computed(() => {
    return props.lokasiKantor?.radius_meter ?? 0;
});

/*
|--------------------------------------------------------------------------
| Helper jarak
|--------------------------------------------------------------------------
*/

function hitungJarakMeter(
    lat1: number,
    lon1: number,
    lat2: number,
    lon2: number,
): number {
    const R = 6371000;

    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

/*
|--------------------------------------------------------------------------
| Kamera
|--------------------------------------------------------------------------
*/

async function mulaiKamera() {
    kameraError.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        kameraError.value =
            'Browser tidak mendukung penggunaan kamera.';
        return;
    }

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
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

        if (video.value) {
            video.value.srcObject = stream.value;

            await video.value.play();

            kameraAktif.value = true;
        }
    } catch (error) {
        console.error(error);

        kameraAktif.value = false;

        kameraError.value =
            'Kamera tidak dapat digunakan. Pastikan izin kamera sudah diberikan pada browser.';
    }
}

function matikanKamera() {
    if (stream.value) {
        stream.value.getTracks().forEach((track) => {
            track.stop();
        });

        stream.value = null;
    }

    kameraAktif.value = false;
}

function ambilFoto() {
    if (!video.value || !canvas.value) {
        return;
    }

    if (!kameraAktif.value) {
        kameraError.value = 'Kamera belum aktif.';
        return;
    }

    const videoWidth = video.value.videoWidth;
    const videoHeight = video.value.videoHeight;

    if (!videoWidth || !videoHeight) {
        kameraError.value =
            'Kamera belum siap. Silakan tunggu beberapa saat.';
        return;
    }

    canvas.value.width = videoWidth;
    canvas.value.height = videoHeight;

    const context = canvas.value.getContext('2d');

    if (!context) {
        return;
    }

    /*
     * Preview kamera dibuat mirror.
     * Foto hasil capture dibalik kembali agar hasil foto
     * tidak mirror.
     */
    context.save();

    context.translate(videoWidth, 0);
    context.scale(-1, 1);

    context.drawImage(
        video.value,
        0,
        0,
        videoWidth,
        videoHeight,
    );

    context.restore();

    const dataUrl = canvas.value.toDataURL('image/jpeg', 0.9);

    fotoPreview.value = dataUrl;

    canvas.value.toBlob(
        (blob) => {
            if (!blob) {
                return;
            }

            const file = new File(
                [blob],
                `absen-masuk-${Date.now()}.jpg`,
                {
                    type: 'image/jpeg',
                },
            );

            fotoFile.value = file;
            form.foto = file;
        },
        'image/jpeg',
        0.9,
    );
}

function ulangFoto() {
    fotoPreview.value = null;
    fotoFile.value = null;
    form.foto = null;

    kameraError.value = '';
}

/*
|--------------------------------------------------------------------------
| GPS
|--------------------------------------------------------------------------
*/

function ambilLokasi() {
    gpsError.value = '';

    if (!navigator.geolocation) {
        gpsError.value =
            'Browser tidak mendukung fitur lokasi.';
        return;
    }

    gpsAktif.value = false;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            latitude.value = position.coords.latitude;
            longitude.value = position.coords.longitude;

            form.latitude = latitude.value;
            form.longitude = longitude.value;

            gpsAktif.value = true;

            if (
                props.lokasiKantor &&
                latitude.value !== null &&
                longitude.value !== null
            ) {
                const latKantor = Number(
                    props.lokasiKantor.latitude,
                );

                const lonKantor = Number(
                    props.lokasiKantor.longitude,
                );

                jarakMeter.value = hitungJarakMeter(
                    latitude.value,
                    longitude.value,
                    latKantor,
                    lonKantor,
                );

                diLuarRadius.value =
                    jarakMeter.value >
                    props.lokasiKantor.radius_meter;
            }
        },
        (error) => {
            console.error(error);

            gpsAktif.value = false;

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    gpsError.value =
                        'Izin lokasi ditolak. Silakan izinkan akses lokasi pada browser.';
                    break;

                case error.POSITION_UNAVAILABLE:
                    gpsError.value =
                        'Lokasi tidak tersedia. Pastikan GPS perangkat aktif.';
                    break;

                case error.TIMEOUT:
                    gpsError.value =
                        'Pengambilan lokasi terlalu lama. Silakan coba lagi.';
                    break;

                default:
                    gpsError.value =
                        'Lokasi tidak dapat ditemukan.';
            }
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
| Status tombol
|--------------------------------------------------------------------------
*/

const bisaAbsen = computed(() => {
    if (props.hariLibur) {
        return false;
    }

    if (props.sudahAbsenMasuk) {
        return false;
    }

    if (!fotoFile.value) {
        return false;
    }

    if (!gpsAktif.value) {
        return false;
    }

    if (diLuarRadius.value) {
        return false;
    }

    return true;
});

/*
|--------------------------------------------------------------------------
| Kirim Absensi
|--------------------------------------------------------------------------
*/

function kirimAbsen() {
    /*
     * Proteksi tambahan.
     * Walaupun halaman sudah menyembunyikan form,
     * backend tetap menjadi pengaman utama.
     */
    if (props.hariLibur) {
        form.setError(
            'foto',
            'Hari ini adalah hari libur. Absensi tidak tersedia.',
        );

        return;
    }

    if (props.sudahAbsenMasuk) {
        return;
    }

    if (!fotoFile.value) {
        form.setError(
            'foto',
            'Silakan ambil foto terlebih dahulu.',
        );

        return;
    }

    if (
        latitude.value === null ||
        longitude.value === null
    ) {
        form.setError(
            'foto',
            'Lokasi belum ditemukan. Silakan aktifkan GPS.',
        );

        return;
    }

    if (diLuarRadius.value) {
        form.setError(
            'foto',
            'Anda berada di luar radius lokasi kantor.',
        );

        return;
    }

    form.foto = fotoFile.value;
    form.latitude = latitude.value;
    form.longitude = longitude.value;

    form.post('/karyawan/absen-masuk', {
        forceFormData: true,
        preserveScroll: true,
    });
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    waktuSekarang.value = new Date();

    clockInterval = setInterval(() => {
        waktuSekarang.value = new Date();
    }, 1000);

    /*
     * Kamera dan GPS tidak dijalankan ketika:
     * - Hari libur (Sabtu, Minggu, hari libur dari database)
     * - Sudah absen masuk
     * - Sudah lewat batas waktu absen masuk
     */
    if (
        !props.hariLibur &&
        !props.sudahAbsenMasuk &&
        !props.batasWaktuTerlewat
    ) {
        mulaiKamera();
        ambilLokasi();
    }
});

onBeforeUnmount(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }

    /*
     * Kamera hanya dimatikan saat pengguna benar-benar
     * meninggalkan/berpindah dari halaman ini.
     */
    matikanKamera();
});
</script>

<template>
    <Head title="Absen Masuk" />

    <div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <!-- Header / Breadcrumb -->
            <div class="mb-6 flex flex-col gap-1">
                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                    <Link href="/karyawan/dashboard" class="transition hover:text-slate-600">
                        Dashboard
                    </Link>
                    <span>›</span>
                    <span class="text-slate-500">Absen Masuk</span>
                </div>

                <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">
                    Absen Masuk Karyawan
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
                            Absensi masuk tidak tersedia pada hari libur.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Batas Waktu Absen Masuk Terlewat -->
            <div
                v-else-if="props.batasWaktuTerlewat"
                class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600"
                    >
                        <AlertCircle class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-red-900">
                            Batas Waktu Absen Masuk Terlewat
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-red-800">
                            Absen masuk hanya bisa dilakukan sampai jam
                            <strong>{{ props.batasAbsenMasuk }}</strong>.
                        </p>

                        <p class="mt-2 text-sm text-red-700">
                            Kamu tidak melakukan absen masuk sebelum batas waktu tersebut,
                            sehingga absen masuk untuk hari ini tidak dapat dilakukan lagi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sudah Absen -->
            <div
                v-else-if="props.sudahAbsenMasuk"
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
                            Sudah Absen Masuk
                        </h2>

                        <p class="mt-1 text-sm text-teal-800">
                            Anda sudah melakukan absensi masuk hari ini.
                        </p>

                        <div
                            v-if="props.absenHariIni"
                            class="mt-3 space-y-1 text-sm text-teal-800"
                        >
                            <p>
                                Jam masuk:
                                <strong>{{ props.absenHariIni.jam_masuk ?? '-' }}</strong>
                            </p>

                            <p>
                                Status:
                                <strong>{{ props.absenHariIni.status ?? '-' }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Absensi -->
            <div v-else class="grid gap-6 lg:grid-cols-3">
                <!-- Kolom Kiri: Kamera + Panduan -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Kamera -->
                    <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Verifikasi Wajah (Selfie Karyawan)
                                </h2>
                            </div>

                            <div
                                v-if="kameraAktif && !fotoPreview"
                                class="flex items-center gap-1.5 text-xs font-semibold text-red-500"
                            >
                                <span class="h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                                REC
                            </div>
                        </div>

                        <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-slate-900">
                            <!-- Preview Kamera: selalu ter-mount (v-show) agar stream tidak
                                 pernah terputus, sehingga saat "Ambil Ulang" kamera langsung aktif -->
                            <video
                                ref="video"
                                v-show="!fotoPreview"
                                autoplay
                                playsinline
                                muted
                                class="h-full w-full object-cover opacity-90"
                                style="transform: scaleX(-1)"
                            />

                            <!-- Preview Foto -->
                            <img
                                v-if="fotoPreview"
                                :src="fotoPreview"
                                alt="Preview foto absensi"
                                class="absolute inset-0 h-full w-full object-cover"
                            />

                            <!-- Overlay instruksi bingkai wajah -->
                            <div
                                v-if="kameraAktif && !fotoPreview"
                                class="pointer-events-none absolute inset-0"
                            >
                                <p class="absolute left-0 right-0 top-3 text-center text-[11px] font-semibold uppercase tracking-wide text-white/90">
                                    Posisikan wajah anda di dalam bingkai kamera
                                </p>

                               
                            </div>

                            <!-- Kamera Tidak Aktif -->
                            <div
                                v-if="!kameraAktif && !fotoPreview"
                                class="absolute inset-0 flex items-center justify-center bg-slate-900/90 p-6 text-center"
                            >
                                <div>
                                    <Camera class="mx-auto h-10 w-10 text-slate-400" />

                                    <p class="mt-3 text-sm text-slate-300">
                                        {{ kameraError || 'Menyalakan kamera...' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <canvas ref="canvas" class="hidden" />

                        <!-- Tombol shutter, di luar bingkai kamera -->
                        <div class="mt-4 flex justify-center">
                            <button
                                v-if="!fotoPreview"
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
                                @click="ulangFoto"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Ambil Ulang
                            </button>
                        </div>

                        <!-- Error Kamera -->
                        <div
                            v-if="kameraError"
                            class="mt-4 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-700"
                        >
                            <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" />
                            <p>{{ kameraError }}</p>
                        </div>
                    </div>

                    <!-- Panduan + Lokasi ringkas -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                            <div class="flex items-center gap-2 text-slate-900">
                                <Info class="h-4 w-4 text-teal-500" />
                                <h3 class="text-sm font-bold">Panduan Presensi</h3>
                            </div>

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Pastikan pencahayaan ruangan cukup terang. Hindari penggunaan
                                kacamata hitam, masker, atau topi yang dapat mengganggu
                                akurasi verifikasi.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                            <div class="flex items-center gap-2 text-slate-900">
                                <MapPin class="h-4 w-4 text-teal-500" />
                                <h3 class="text-sm font-bold">Lokasi Terverifikasi GPS</h3>
                            </div>

                            <template v-if="lokasiTersedia">
                                <p class="mt-2 text-xs font-semibold text-slate-700">
                                    {{ props.lokasiKantor?.nama_lokasi }}
                                </p>

                                <p
                                    v-if="!diLuarRadius"
                                    class="mt-0.5 text-xs font-medium text-teal-600"
                                >
                                    Presensi diizinkan (Radius presisi {{ radiusKantor }}m)
                                </p>

                                <p v-else class="mt-0.5 text-xs font-medium text-red-500">
                                    Di luar radius ({{ radiusKantor }}m)
                                </p>
                            </template>

                            <p v-else class="mt-2 text-xs text-red-500">
                                Lokasi kantor belum dikonfigurasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Waktu + Detail Absen -->
                <div class="space-y-6">
                    <!-- Waktu Saat Ini -->
                    <div class="rounded-2xl bg-slate-900 p-6 text-center shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-400">
                            Waktu Saat Ini
                        </p>

                        <p class="mt-2 text-4xl font-bold tracking-wide text-white">
                            {{ jamSekarang }}
                        </p>

                        <p class="mt-1 text-sm capitalize text-slate-400">
                            {{ tanggalSekarang }}
                        </p>
                    </div>

                    <!-- Detail Absen Masuk -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                        <h2 class="font-bold text-slate-900">Detail Absen Masuk</h2>

                        <!-- Data ringkas -->
                        <dl class="mt-4 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Nama Karyawan</dt>
                                <dd class="font-semibold text-slate-900">{{ namaKaryawan }}</dd>
                            </div>

                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Tanggal Kerja</dt>
                                <dd class="font-semibold text-slate-900">{{ tanggalSekarang }}</dd>
                            </div>

                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-400">Jam Masuk Kerja</dt>
                                <dd class="font-semibold text-slate-900">{{ jamMasukTampil }}</dd>
                            </div>
                        </dl>

                        <!-- Catatan toleransi -->
                        <div class="mt-4 flex items-start gap-2 rounded-xl bg-teal-50 p-3">
                            <Info class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" />
                            <p class="text-xs leading-5 text-teal-700">
                                Batas toleransi keterlambatan 1 jam dari jam masuk kerja.
                            </p>
                        </div>

                        <!-- Status lokasi -->
                        <div class="mt-4 space-y-2">
                            <div
                                v-if="gpsAktif"
                                class="flex items-start gap-2 rounded-xl bg-slate-50 p-3"
                            >
                                <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" />
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">Lokasi ditemukan</p>
                                    <p v-if="jarakMeter !== null" class="mt-0.5 text-xs text-slate-500">
                                        Jarak dari kantor: {{ Math.round(jarakMeter) }} meter
                                    </p>
                                </div>
                            </div>

                            <div v-else class="rounded-xl bg-slate-50 p-3">
                                <p class="text-xs text-slate-500">
                                    {{ gpsError || 'Mencari lokasi...' }}
                                </p>
                            </div>

                            <div
                                v-if="diLuarRadius"
                                class="flex items-start gap-2 rounded-xl bg-red-50 p-3"
                            >
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                                <p class="text-xs text-red-600">
                                    Anda berada di luar radius kantor. Absensi tidak dapat dilakukan.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="ambilLokasi"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                <MapPin class="h-3.5 w-3.5" />
                                Perbarui Lokasi
                            </button>
                        </div>

                        <!-- Foto preview -->
                        <div
                            v-if="fotoPreview"
                            class="mt-4 overflow-hidden rounded-xl border border-slate-100"
                        >
                            <img
                                :src="fotoPreview"
                                alt="Preview foto absensi"
                                class="aspect-square w-full object-cover"
                            />
                        </div>

                        <!-- Submit -->
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <button
                                type="button"
                                @click="kirimAbsen"
                                :disabled="!bisaAbsen || form.processing"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <CheckCircle2 class="h-5 w-5" />
                                {{ form.processing ? 'Mengirim...' : 'Kirim Absen Masuk' }}
                            </button>

                            <p v-if="form.errors.foto" class="mt-3 text-center text-xs text-red-600">
                                {{ form.errors.foto }}
                            </p>

                            <p v-if="!fotoFile" class="mt-3 text-center text-xs text-slate-400">
                                Ambil foto terlebih dahulu.
                            </p>

                            <p v-else-if="!gpsAktif" class="mt-3 text-center text-xs text-slate-400">
                                Menunggu lokasi GPS.
                            </p>

                            <p v-else-if="diLuarRadius" class="mt-3 text-center text-xs text-red-500">
                                Anda berada di luar radius kantor.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>