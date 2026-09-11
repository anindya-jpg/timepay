<script setup lang="ts">
import { computed } from 'vue';

interface Stats {
    karyawanAktif: number;
    karyawanNonaktif: number;
    totalHadirHariIni: number;
    totalTidakHadirHariIni: number;
    lemburMenungguApproval: number;
}

interface RingkasanPenggajian {
    bulan: number;
    tahun: number;
    final: number;
    draft: number;
}

interface Aktivitas {
    id_absensi: number;
    id_karyawan: number;
    tanggal: string;
    status: string;
    karyawan?: {
        id_karyawan: number;
        nama: string;
    };
}

const props = defineProps<{
    stats: Stats;
    distribusiStatus: Record<string, number>;
    ringkasanPenggajian: RingkasanPenggajian;
    aktivitasTerbaru: Aktivitas[];
}>();

const totalAbsensi = computed(() => {
    return Object.values(props.distribusiStatus).reduce(
        (total, value) => total + Number(value),
        0,
    );
});

const persentaseHadir = computed(() => {
    if (totalAbsensi.value === 0) {
        return 0;
    }

    return Math.round(
        ((props.distribusiStatus.Hadir ?? 0) / totalAbsensi.value) * 100,
    );
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Dashboard
            </h1>

            <p class="text-sm text-muted-foreground">
                Ringkasan data TimePay hari ini.
            </p>
        </div>

        <!-- Statistik -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    Karyawan Aktif
                </p>

                <p class="mt-2 text-3xl font-bold">
                    {{ stats.karyawanAktif }}
                </p>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    Hadir Hari Ini
                </p>

                <p class="mt-2 text-3xl font-bold">
                    {{ stats.totalHadirHariIni }}
                </p>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    Tidak Hadir Hari Ini
                </p>

                <p class="mt-2 text-3xl font-bold">
                    {{ stats.totalTidakHadirHariIni }}
                </p>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm text-muted-foreground">
                    Lembur Menunggu Approval
                </p>

                <p class="mt-2 text-3xl font-bold">
                    {{ stats.lemburMenungguApproval }}
                </p>
            </div>
        </div>

        <!-- Konten -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Absensi -->
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="font-semibold">
                        Status Absensi Bulan Ini
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        Total {{ totalAbsensi }} data absensi
                    </p>
                </div>

                <div class="flex items-center gap-8">
                    <div
                        class="flex h-32 w-32 items-center justify-center rounded-full border-8"
                    >
                        <div class="text-center">
                            <p class="text-2xl font-bold">
                                {{ persentaseHadir }}%
                            </p>

                            <p class="text-xs text-muted-foreground">
                                Hadir
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div
                            v-for="(total, status) in distribusiStatus"
                            :key="status"
                            class="flex items-center justify-between gap-8"
                        >
                            <span>{{ status }}</span>
                            <span class="font-semibold">
                                {{ total }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penggajian -->
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="font-semibold">
                        Ringkasan Penggajian
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        {{ ringkasanPenggajian.bulan }}/{{
                            ringkasanPenggajian.tahun
                        }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>Draft</span>
                            <span class="font-semibold">
                                {{ ringkasanPenggajian.draft }}
                            </span>
                        </div>

                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-yellow-500"
                                :style="{
                                    width: `${ringkasanPenggajian.draft > 0 ? 100 : 0}%`,
                                }"
                            ></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span>Final</span>
                            <span class="font-semibold">
                                {{ ringkasanPenggajian.final }}
                            </span>
                        </div>

                        <div class="h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-green-500"
                                :style="{
                                    width: `${ringkasanPenggajian.final > 0 ? 100 : 0}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aktivitas -->
        <div class="rounded-xl border bg-card shadow-sm">
            <div class="border-b p-6">
                <h2 class="font-semibold">
                    Aktivitas Absensi Terbaru
                </h2>

                <p class="text-sm text-muted-foreground">
                    Lima data absensi terbaru.
                </p>
            </div>

            <div
                v-if="aktivitasTerbaru.length === 0"
                class="p-6 text-center text-sm text-muted-foreground"
            >
                Belum ada aktivitas absensi.
            </div>

            <div v-else class="divide-y">
                <div
                    v-for="aktivitas in aktivitasTerbaru"
                    :key="aktivitas.id_absensi"
                    class="flex items-center justify-between p-4"
                >
                    <div>
                        <p class="font-medium">
                            {{ aktivitas.karyawan?.nama ?? 'Karyawan' }}
                        </p>

                        <p class="text-sm text-muted-foreground">
                            {{ aktivitas.tanggal }}
                        </p>
                    </div>

                    <span class="text-sm font-medium">
                        {{ aktivitas.status }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>