```vue
<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Printer,
    CircleAlert,
    MessageCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';

/*
|--------------------------------------------------------------------------
| TYPES
|--------------------------------------------------------------------------
*/

type Jabatan = {
    nama_jabatan: string;
};

type Karyawan = {
    nama: string;
    nip: string;
    no_rekening: string | null;
    nama_bank: string | null;
    foto: string | null;
    jabatan: Jabatan;
};

type Penggajian = {
    id_gaji: number;
    bulan: number;
    tahun: number;
    gaji_pokok: number;
    total_tunjangan: number;
    total_jam_lembur: number;
    total_tunjangan_lembur: number;
    total_potongan: number;
    jumlah_hadir: number;
    jumlah_telat: number;
    jumlah_alpha: number;
    gaji_bersih: number;
    status: 'draft' | 'final';
    tanggal_generate: string | null;
    karyawan: Karyawan;
} | null;

type RincianTunjangan = {
    nama: string;
    nominal: number;
};

type RincianPotongan = {
    nama: string;
    keterangan: string;
    nominal: number;
};

/*
|--------------------------------------------------------------------------
| PROPS
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    penggajian: Penggajian;

    jumlahIzin: number;

    periodeTersedia: {
        bulan: number;
        tahun: number;
        label: string;
    }[];

    filters: {
        bulan: number;
        tahun: number;
    };

    rincianTunjangan: RincianTunjangan[];

    rincianPotongan: RincianPotongan[];

    totalPendapatan: number;
}>();

/*
|--------------------------------------------------------------------------
| PERIODE
|--------------------------------------------------------------------------
*/

const periodeValue = ref(
    `${props.filters.bulan}-${props.filters.tahun}`,
);

function pindahPeriode(value: string) {
    const [bulan, tahun] = value.split('-');

    router.get(
        '/karyawan/slip-gaji',
        {
            bulan,
            tahun,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

/*
|--------------------------------------------------------------------------
| FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

function formatRupiah(angka: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(angka);
}

/*
|--------------------------------------------------------------------------
| REKENING
|--------------------------------------------------------------------------
*/

function maskRekening(
    noRekening: string | null,
) {
    if (!noRekening) {
        return '-';
    }

    const bersih =
        noRekening.replace(/\s/g, '');

    if (bersih.length <= 4) {
        return bersih;
    }

    return `****${bersih.slice(-4)}`;
}

/*
|--------------------------------------------------------------------------
| TANGGAL
|--------------------------------------------------------------------------
*/

function formatTanggal(
    tanggal: string | null,
) {
    if (!tanggal) {
        return '-';
    }

    return new Date(tanggal).toLocaleDateString(
        'id-ID',
        {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        },
    );
}

/*
|--------------------------------------------------------------------------
| LABEL PERIODE
|--------------------------------------------------------------------------
*/

const periodeLabel = computed(() => {
    const periode =
        props.periodeTersedia.find(
            (item) =>
                item.bulan ===
                    props.filters.bulan &&
                item.tahun ===
                    props.filters.tahun,
        );

    return (
        periode?.label ??
        `${props.filters.bulan}/${props.filters.tahun}`
    );
});

/*
|--------------------------------------------------------------------------
| STATUS SLIP
|--------------------------------------------------------------------------
*/

const slipSudahFinal = computed(() => {
    return (
        props.penggajian?.status ===
        'final'
    );
});

/*
|--------------------------------------------------------------------------
| TERBILANG
|--------------------------------------------------------------------------
*/

const satuan = [
    '',
    'Satu',
    'Dua',
    'Tiga',
    'Empat',
    'Lima',
    'Enam',
    'Tujuh',
    'Delapan',
    'Sembilan',
    'Sepuluh',
    'Sebelas',
];

function terbilang(
    n: number,
): string {
    n = Math.floor(n);

    if (n < 12) {
        return satuan[n];
    }

    if (n < 20) {
        return `${terbilang(n - 10)} Belas`;
    }

    if (n < 100) {
        return `${terbilang(
            Math.floor(n / 10),
        )} Puluh ${terbilang(
            n % 10,
        )}`.trim();
    }

    if (n < 200) {
        return `Seratus ${terbilang(
            n - 100,
        )}`.trim();
    }

    if (n < 1000) {
        return `${terbilang(
            Math.floor(n / 100),
        )} Ratus ${terbilang(
            n % 100,
        )}`.trim();
    }

    if (n < 2000) {
        return `Seribu ${terbilang(
            n - 1000,
        )}`.trim();
    }

    if (n < 1000000) {
        return `${terbilang(
            Math.floor(n / 1000),
        )} Ribu ${terbilang(
            n % 1000,
        )}`.trim();
    }

    if (n < 1000000000) {
        return `${terbilang(
            Math.floor(n / 1000000),
        )} Juta ${terbilang(
            n % 1000000,
        )}`.trim();
    }

    return `${terbilang(
        Math.floor(n / 1000000000),
    )} Miliar ${terbilang(
        n % 1000000000,
    )}`.trim();
}

const terbilangGaji = computed(() => {
    if (!props.penggajian) {
        return '';
    }

    return `${terbilang(
        props.penggajian.gaji_bersih,
    )
        .replace(/\s+/g, ' ')
        .trim()} Rupiah`;
});

/*
|--------------------------------------------------------------------------
| WHATSAPP FINANCE / HRD
|--------------------------------------------------------------------------
*/

const NOMOR_WA_FINANCE =
    '6282218678100';

const linkWhatsappFinance =
    computed(() => {
        if (!props.penggajian) {
            return `https://wa.me/${NOMOR_WA_FINANCE}`;
        }

        const pesan =
            encodeURIComponent(
                `Halo Finance/HRD, saya ${props.penggajian.karyawan.nama} (NIP: ${props.penggajian.karyawan.nip}) ingin menanyakan terkait slip gaji periode ${periodeLabel.value}.`,
            );

        return `https://wa.me/${NOMOR_WA_FINANCE}?text=${pesan}`;
    });

/*
|--------------------------------------------------------------------------
| CETAK / DOWNLOAD PDF
|--------------------------------------------------------------------------
|
| PDF HANYA BOLEH DIUNDUH JIKA STATUS = FINAL
|
*/

const sedangMengunduh =
    ref(false);

async function cetakPdf() {
    /*
     * Pengaman pertama:
     * tidak ada data slip
     */
    if (!props.penggajian) {
        return;
    }

    /*
     * Pengaman kedua:
     * slip masih draft
     */
    if (
        props.penggajian.status !==
        'final'
    ) {
        alert(
            'Slip gaji belum difinalisasi sehingga belum dapat diunduh.',
        );

        return;
    }

    /*
     * Pengaman ketiga:
     * sedang download
     */
    if (sedangMengunduh.value) {
        return;
    }

    sedangMengunduh.value = true;

    try {
        const url =
            `/karyawan/slip-gaji/pdf?bulan=${props.filters.bulan}&tahun=${props.filters.tahun}`;

        const response =
            await fetch(url, {
                headers: {
                    Accept:
                        'application/pdf',
                },
            });

        if (!response.ok) {
            throw new Error(
                'Gagal mengambil file PDF.',
            );
        }

        const blob =
            await response.blob();

        const blobUrl =
            window.URL.createObjectURL(
                blob,
            );

        const namaFile =
            `Slip-Gaji-${props.penggajian.karyawan.nama.replace(/\s+/g, '-')}-${periodeLabel.value.replace(/\s+/g, '-')}.pdf`;

        const link =
            document.createElement(
                'a',
            );

        link.href = blobUrl;

        link.download =
            namaFile;

        document.body.appendChild(
            link,
        );

        link.click();

        document.body.removeChild(
            link,
        );

        window.URL.revokeObjectURL(
            blobUrl,
        );
    } catch (error) {
        console.error(error);

        alert(
            'Gagal mengunduh slip gaji. Silakan coba lagi.',
        );
    } finally {
        sedangMengunduh.value =
            false;
    }
}
</script>

<template>
    <Head title="Slip Gaji" />

    <div class="w-full space-y-4">

        <!-- ========================================================= -->
        <!-- HEADER -->
        <!-- ========================================================= -->

        <div
            class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div
                        class="mb-2 flex items-center gap-2"
                    >
                        <span
                            class="inline-flex rounded bg-slate-900 px-2 py-1 text-xs font-bold tracking-wide text-white"
                        >
                            SLIP GAJI KARYAWAN
                        </span>

                        <span
                            v-if="penggajian"
                            class="text-xs text-slate-400"
                        >
                            ID:
                            SLP{{
                                String(
                                    penggajian.id_gaji,
                                ).padStart(
                                    3,
                                    '0',
                                )
                            }}
                        </span>
                    </div>

                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-800"
                    >
                        Detail Slip Gaji Anda
                    </h1>

                    <p
                        class="mt-1 max-w-2xl text-sm leading-5 text-slate-500"
                    >
                        Informasi rincian kompensasi
                        gaji pokok, tunjangan kinerja,
                        potongan resmi, dan lembur
                        untuk periode pembayaran
                        {{ periodeLabel }}.
                    </p>
                </div>

                <div
                    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row"
                >
                    <select
                        v-model="periodeValue"
                        class="h-10 min-w-[140px] rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                        @change="
                            pindahPeriode(
                                periodeValue,
                            )
                        "
                    >
                        <option
                            v-for="periode in periodeTersedia"
                            :key="`${periode.bulan}-${periode.tahun}`"
                            :value="`${periode.bulan}-${periode.tahun}`"
                        >
                            {{ periode.label }}
                        </option>
                    </select>

                    <!-- ================================================= -->
                    <!-- TOMBOL DOWNLOAD -->
                    <!-- ================================================= -->

                    <button
                        type="button"
                        :disabled="
                            !penggajian ||
                            !slipSudahFinal ||
                            sedangMengunduh
                        "
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md px-4 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                        :class="
                            slipSudahFinal
                                ? 'bg-teal-500 hover:bg-teal-600'
                                : 'bg-slate-400'
                        "
                        @click="cetakPdf"
                    >
                        <Printer class="size-4" />

                        {{
                            sedangMengunduh
                                ? 'Mengunduh...'
                                : slipSudahFinal
                                    ? 'Cetak Slip Gaji'
                                    : 'Menunggu Finalisasi'
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TIDAK ADA SLIP -->
        <!-- ========================================================= -->

        <div
            v-if="!penggajian"
            class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center"
        >
            <p
                class="text-base font-medium text-slate-700"
            >
                Belum ada slip gaji untuk
                periode ini.
            </p>

            <p
                class="mt-1 text-sm text-slate-400"
            >
                Silakan pilih periode pembayaran
                yang tersedia.
            </p>
        </div>

        <!-- ========================================================= -->
        <!-- CONTENT -->
        <!-- ========================================================= -->

        <div
            v-else
            class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(250px,0.9fr)]"
        >

            <!-- ===================================================== -->
            <!-- SLIP GAJI -->
            <!-- ===================================================== -->

            <div
                class="overflow-hidden rounded-xl border border-slate-200 border-l-4 border-l-teal-500 bg-white shadow-sm"
            >
                <div class="p-5">

                    <!-- ================================================= -->
                    <!-- IDENTITAS -->
                    <!-- ================================================= -->

                    <div
                        class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100"
                            >
                                <img
                                    v-if="
                                        penggajian
                                            .karyawan
                                            .foto
                                    "
                                    :src="
                                        penggajian
                                            .karyawan
                                            .foto
                                            .startsWith(
                                                'http',
                                            )
                                            ? penggajian
                                                  .karyawan
                                                  .foto
                                            : `/storage/${penggajian.karyawan.foto}`
                                    "
                                    :alt="
                                        penggajian
                                            .karyawan
                                            .nama
                                    "
                                    class="size-full object-cover"
                                />

                                <span
                                    v-else
                                    class="text-base font-bold text-slate-400"
                                >
                                    {{
                                        penggajian
                                            .karyawan
                                            .nama
                                            .charAt(
                                                0,
                                            )
                                            .toUpperCase()
                                    }}
                                </span>
                            </div>

                            <div
                                class="min-w-0"
                            >
                                <p
                                    class="truncate text-base font-bold text-slate-800"
                                >
                                    {{
                                        penggajian
                                            .karyawan
                                            .nama
                                    }}
                                </p>

                                <p
                                    class="text-xs text-slate-400"
                                >
                                    NIP:
                                    {{
                                        penggajian
                                            .karyawan
                                            .nip
                                    }}

                                    <span
                                        class="mx-1"
                                    >
                                        •
                                    </span>

                                    {{
                                        penggajian
                                            .karyawan
                                            .jabatan
                                            .nama_jabatan
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs font-medium text-slate-500"
                                >
                                    Ditransfer pada BANK
                                    {{
                                        penggajian
                                            .karyawan
                                            .nama_bank ??
                                        'BCA'
                                    }}
                                    No. Rekening
                                    {{
                                        maskRekening(
                                            penggajian
                                                .karyawan
                                                .no_rekening,
                                        )
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-slate-400"
                                >
                                    No rekening:
                                    {{
                                        maskRekening(
                                            penggajian
                                                .karyawan
                                                .no_rekening,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="text-left sm:text-right"
                        >
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                :class="
                                    penggajian.status ===
                                    'final'
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-amber-50 text-amber-600'
                                "
                            >
                                {{
                                    penggajian.status ===
                                    'final'
                                        ? 'SUDAH DIBAYAR'
                                        : 'DRAFT'
                                }}
                            </span>

                            <p
                                v-if="
                                    penggajian.tanggal_generate
                                "
                                class="mt-1 text-xs text-slate-400"
                            >
                                Dibayarkan pada
                                {{
                                    formatTanggal(
                                        penggajian
                                            .tanggal_generate,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- RINCIAN PENDAPATAN -->
                    <!-- ================================================= -->

                    <div class="mt-5">
                        <div
                            class="mb-3 flex items-center justify-between gap-3"
                        >
                            <h2
                                class="text-sm font-bold text-slate-800"
                            >
                                Rincian Pendapatan
                                & Tunjangan
                            </h2>

                            <span
                                class="text-sm font-bold text-slate-800"
                            >
                                Total
                                {{
                                    formatRupiah(
                                        totalPendapatan,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="space-y-0.5"
                        >

                            <!-- Gaji Pokok -->
                            <div
                                class="flex items-center justify-between border-b border-slate-50 py-2.5 text-sm"
                            >
                                <span
                                    class="text-slate-500"
                                >
                                    Gaji Pokok
                                </span>

                                <span
                                    class="font-semibold text-slate-700"
                                >
                                    {{
                                        formatRupiah(
                                            penggajian
                                                .gaji_pokok,
                                        )
                                    }}
                                </span>
                            </div>

                            <!-- Tunjangan -->
                            <div
                                v-for="tunjangan in rincianTunjangan"
                                :key="`tunjangan-${tunjangan.nama}`"
                                class="flex items-center justify-between border-b border-slate-50 py-2.5 text-sm"
                            >
                                <span
                                    class="text-slate-500"
                                >
                                    {{
                                        tunjangan.nama
                                    }}
                                </span>

                                <span
                                    class="font-semibold text-slate-700"
                                >
                                    {{
                                        formatRupiah(
                                            tunjangan.nominal,
                                        )
                                    }}
                                </span>
                            </div>

                            <!-- Lembur -->
                            <div
                                class="flex items-center justify-between border-b border-slate-50 py-2.5 text-sm"
                            >
                                <span
                                    class="text-slate-500"
                                >
                                    Lembur
                                    ({{
                                        penggajian
                                            .total_jam_lembur
                                    }}
                                    jam)
                                </span>

                                <span
                                    class="font-semibold text-emerald-500"
                                >
                                    +
                                    {{
                                        formatRupiah(
                                            penggajian
                                                .total_tunjangan_lembur,
                                        )
                                    }}
                                </span>
                            </div>

                            <!-- Total Pendapatan -->
                            <div
                                class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 text-sm"
                            >
                                <span
                                    class="font-bold text-slate-700"
                                >
                                    Total Pendapatan
                                </span>

                                <span
                                    class="font-bold text-slate-800"
                                >
                                    {{
                                        formatRupiah(
                                            totalPendapatan,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- RINCIAN POTONGAN -->
                    <!-- ================================================= -->

                    <div class="mt-6">
                        <div
                            class="mb-3 flex items-center justify-between gap-3"
                        >
                            <h2
                                class="text-sm font-bold text-slate-800"
                            >
                                Rincian Potongan
                            </h2>

                            <span
                                class="text-sm font-bold text-red-500"
                            >
                                -
                                {{
                                    formatRupiah(
                                        penggajian
                                            .total_potongan,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="space-y-0.5"
                        >

                            <!-- Semua Potongan -->
                            <div
                                v-for="potongan in rincianPotongan"
                                :key="`potongan-${potongan.nama}`"
                                class="flex items-center justify-between border-b border-slate-50 py-2.5 text-sm"
                            >
                                <div
                                    class="flex flex-col"
                                >
                                    <span
                                        class="text-slate-500"
                                    >
                                        {{
                                            potongan.nama
                                        }}
                                    </span>

                                    <span
                                        v-if="
                                            potongan.keterangan
                                        "
                                        class="mt-0.5 text-xs text-slate-400"
                                    >
                                        {{
                                            potongan.keterangan
                                        }}
                                    </span>
                                </div>

                                <span
                                    class="font-semibold text-red-500"
                                >
                                    -
                                    {{
                                        formatRupiah(
                                            potongan.nominal,
                                        )
                                    }}
                                </span>
                            </div>

                            <!-- Tidak ada potongan -->
                            <div
                                v-if="
                                    rincianPotongan.length ===
                                    0
                                "
                                class="py-4 text-center text-sm text-slate-400"
                            >
                                Tidak ada potongan
                                pada periode ini.
                            </div>

                            <!-- Total Potongan -->
                            <div
                                class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 text-sm"
                            >
                                <span
                                    class="font-bold text-slate-700"
                                >
                                    Total Potongan
                                </span>

                                <span
                                    class="font-bold text-red-500"
                                >
                                    -
                                    {{
                                        formatRupiah(
                                            penggajian
                                                .total_potongan,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- TOTAL GAJI BERSIH -->
                    <!-- ================================================= -->

                    <div
                        class="mt-6 rounded-lg bg-slate-900 px-4 py-4 text-white"
                    >
                        <div
                            class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <span
                                class="text-sm text-slate-300"
                            >
                                Total Pendapatan
                                Diterima
                                (Gaji Bersih)
                            </span>

                            <span
                                class="text-xl font-bold text-teal-400"
                            >
                                {{
                                    formatRupiah(
                                        penggajian
                                            .gaji_bersih,
                                    )
                                }}
                            </span>
                        </div>

                        <p
                            class="mt-1 text-xs text-slate-400"
                        >
                            Ditransfer ke Rekening
                            {{
                                maskRekening(
                                    penggajian
                                        .karyawan
                                        .no_rekening,
                                )
                            }}
                        </p>
                    </div>

                    <!-- ================================================= -->
                    <!-- TERBILANG -->
                    <!-- ================================================= -->

                    <div class="mt-5">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Terbilang
                        </p>

                        <p
                            class="mt-1 text-sm italic leading-5 text-slate-700"
                        >
                            "{{ terbilangGaji }}"
                        </p>
                    </div>
                <!-- DISCLAIMER -->
                    <div
                        class="mt-5 border-t border-slate-100 pt-5"
                    >
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-slate-400"
                        >
                            Disclaimer
                        </p>

                        <p
                            class="mt-1 max-w-2xl text-xs leading-5 text-slate-400"
                        >
                            Slip gaji ini dibuat secara
                            elektronik oleh sistem
                            kepegawaian internal
                            perusahaan. Segala perbedaan
                            perhitungan wajib
                            dikoordinasikan langsung ke
                            bagian Finance atau HRD
                            selambat-lambatnya 3 hari
                            setelah tanggal pembayaran.
                        </p>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR KANAN -->
            <div
                class="flex flex-col gap-4"
            >

                <!-- RINGKASAN ABSENSI -->
            
                <div
                    class="rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="p-5">
                        <h2
                            class="text-sm font-bold text-slate-800"
                        >
                            Ringkasan Absensi
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-400"
                        >
                            Berdasarkan log kerja
                            {{ periodeLabel }}
                        </p>

                        <div
                            class="mt-5 space-y-4"
                        >

                            <!-- Kehadiran -->
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-slate-500"
                                >
                                    Kehadiran Kerja
                                </span>

                                <span
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    {{
                                        penggajian
                                            .jumlah_hadir
                                    }}
                                    hari
                                </span>
                            </div>

                            <!-- Lembur -->
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-slate-500"
                                >
                                    Total Jam Lembur
                                </span>

                                <span
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    {{
                                        penggajian
                                            .total_jam_lembur
                                    }}
                                    jam
                                </span>
                            </div>

                            <!-- Izin -->
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-slate-500"
                                >
                                    Status Izin
                                </span>

                                <span
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    {{ jumlahIzin }}
                                    hari
                                </span>
                            </div>

                            <!-- Telat -->
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-slate-500"
                                >
                                    Terlambat
                                </span>

                                <span
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    {{
                                        penggajian
                                            .jumlah_telat
                                    }}
                                    kali
                                </span>
                            </div>

                            <!-- Alpha -->
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs text-slate-500"
                                >
                                    Alpha
                                </span>

                                <span
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    {{
                                        penggajian
                                            .jumlah_alpha
                                    }}
                                    hari
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- SELISIH PERHITUNGAN -->
                <!-- ================================================= -->

                <div
                    class="rounded-xl border border-teal-100 bg-white shadow-sm"
                >
                    <div class="p-5">

                        <div
                            class="flex items-start gap-2"
                        >
                            <CircleAlert
                                class="mt-0.5 size-4 shrink-0 text-teal-500"
                            />

                            <h2
                                class="text-sm font-bold text-slate-800"
                            >
                                Ada Selisih
                                Perhitungan?
                            </h2>
                        </div>

                        <p
                            class="mt-2 text-xs leading-5 text-slate-400"
                        >
                            Silakan hubungi tim
                            Finance atau HRD dengan
                            melampirkan laporan
                            absensi dan slip gaji
                            sesuai dengan jabatan
                            Anda.
                        </p>

                        <a
                           :href="
                                linkWhatsappFinance
                            "
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 flex h-9 w-full items-center justify-center gap-2 rounded-md border border-slate-700 bg-white px-3 text-xs font-bold uppercase text-slate-700 transition hover:bg-slate-50"
                        >
                            <MessageCircle
                                class="size-4"
                            />
                            Hubungi Finance / HRD
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
