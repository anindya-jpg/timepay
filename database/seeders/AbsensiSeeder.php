<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AbsensiSeeder extends Seeder
{
    /**
     * Jam kerja standar (sesuai tabel pengaturan_jam_kerja).
     */
    protected string $jamMasukStandar = '09:00:00';
    protected string $jamPulangStandar = '17:00:00';

    public function run(): void
    {
        // =========================================================
        // 1. HAPUS DUMMY DATA ABSENSI YANG LAMA
        // =========================================================
        Schema::disableForeignKeyConstraints();
        DB::table('absensi')->truncate(); // reset juga auto_increment id_absensi
        Schema::enableForeignKeyConstraints();

        // =========================================================
        // 2. AMBIL KARYAWAN YANG STATUSNYA AKTIF
        // =========================================================
        $karyawanList = DB::table('karyawan')
            ->where('status_aktif', 'aktif')
            ->pluck('id_karyawan');

        // =========================================================
        // 2b. AMBIL DAFTAR HARI LIBUR (tabel hari_libur)
        // =========================================================
        $hariLibur = DB::table('hari_libur')
            ->pluck('tanggal')
            ->map(fn ($tgl) => Carbon::parse($tgl)->toDateString())
            ->toArray();

        // =========================================================
        // 3. GENERATE DUMMY ABSENSI: 1 Juli 2026 - 4 September 2026
        // =========================================================
        $startDate = Carbon::parse('2026-07-01');
        $endDate   = Carbon::parse('2026-09-04');

        $rows = [];

        foreach ($karyawanList as $idKaryawan) {
            $tanggal = $startDate->copy();

            while ($tanggal->lte($endDate)) {
                // Lewati Sabtu & Minggu
                if ($tanggal->isWeekend()) {
                    $tanggal->addDay();
                    continue;
                }

                // Lewati tanggal yang ada di tabel hari_libur
                if (in_array($tanggal->toDateString(), $hariLibur, true)) {
                    $tanggal->addDay();
                    continue;
                }

                $rows[] = $this->buatBarisAbsensi($idKaryawan, $tanggal->copy());

                $tanggal->addDay();
            }
        }

        // Insert per-chunk biar aman untuk data yang banyak
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('absensi')->insert($chunk);
        }

        $this->command?->info('Dummy absensi berhasil dibuat: ' . count($rows) . ' baris.');
    }

    /**
     * Bangun satu baris data absensi dengan variasi status yang realistis.
     */
    protected function buatBarisAbsensi(int $idKaryawan, Carbon $tanggal): array
    {
        $roll = rand(1, 100);

        // Distribusi status:
        // 82% Hadir, 6% Telat, 4% Pulang Cepat, 3% Alpha, 3% Izin, 2% Sakit
        if ($roll <= 82) {
            $status = 'Hadir';
        } elseif ($roll <= 88) {
            $status = 'Telat';
        } elseif ($roll <= 92) {
            $status = 'Pulang Cepat';
        } elseif ($roll <= 95) {
            $status = 'Alpha';
        } elseif ($roll <= 98) {
            $status = 'Izin';
        } else {
            $status = 'Sakit';
        }

        $jamMasuk        = null;
        $jamPulang       = null;
        $menitTelat      = 0;
        $jamLembur       = 0.00;
        $keteranganLembur = null;
        $statusApproval  = null;

        switch ($status) {
            case 'Hadir':
                // Datang antara 08:45 - 08:59 (tepat waktu)
                $jamMasuk  = $tanggal->copy()->setTime(8, rand(45, 59), rand(0, 59))->format('H:i:s');
                $jamPulang = $tanggal->copy()->setTime(17, rand(0, 20), 0)->format('H:i:s');

                // Sesekali (±10%) ada lembur
                if (rand(1, 100) <= 10) {
                    $tambahJam    = rand(1, 4);
                    $jamPulangObj = $tanggal->copy()->setTime(17, 0, 0)->addHours($tambahJam);
                    $jamPulang    = $jamPulangObj->format('H:i:s');
                    $jamLembur    = (float) $tambahJam;
                    $keteranganLembur = collect([
                        'Menyelesaikan pekerjaan tambahan',
                        'Menyelesaikan laporan bulanan',
                        'Membantu closing target tim',
                        'Menyelesaikan tugas mendesak dari atasan',
                    ])->random();
                    $statusApproval = collect(['approved', 'approved', 'pending'])->random();
                }
                break;

            case 'Telat':
                $jamMasuk   = $tanggal->copy()->setTime(9, rand(5, 45), rand(0, 59))->format('H:i:s');
                $jamPulang  = $tanggal->copy()->setTime(17, rand(0, 15), 0)->format('H:i:s');
                $standarDetik = strtotime($this->jamMasukStandar);
                $masukDetik   = strtotime($jamMasuk);
                $menitTelat   = max(0, (int) round(($masukDetik - $standarDetik) / 60));
                break;

            case 'Pulang Cepat':
                $jamMasuk  = $tanggal->copy()->setTime(8, rand(45, 59), 0)->format('H:i:s');
                $jamPulang = $tanggal->copy()->setTime(rand(13, 15), rand(0, 59), 0)->format('H:i:s');
                break;

            case 'Alpha':
            case 'Izin':
            case 'Sakit':
                // Tidak ada jam masuk/pulang
                break;
        }

        return [
            'id_karyawan'             => $idKaryawan,
            'tanggal'                 => $tanggal->toDateString(),
            'jam_masuk'               => $jamMasuk,
            'jam_pulang'              => $jamPulang,
            'foto_masuk'              => null,
            'lokasi_masuk_lat'        => null,
            'lokasi_masuk_lng'        => null,
            'jarak_masuk_meter'       => null,
            'foto_pulang'             => null,
            'lokasi_pulang_lat'       => null,
            'lokasi_pulang_lng'       => null,
            'jarak_pulang_meter'      => null,
            'status'                  => $status,
            'menit_telat'             => $menitTelat,
            'jam_lembur'              => $jamLembur,
            'keterangan_lembur'       => $keteranganLembur,
            'status_approval_lembur'  => $statusApproval,
            'created_at'              => Carbon::now(),
        ];
    }
}