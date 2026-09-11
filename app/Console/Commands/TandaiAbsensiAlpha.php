<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Karyawan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TandaiAbsensiAlpha extends Command
{
    /*
    |--------------------------------------------------------------------------
    | TIMEZONE SISTEM
    |--------------------------------------------------------------------------
    */

    protected string $timezone = 'Asia/Jakarta';

    /**
     * Nama & signature command.
     *
     * Jalankan manual untuk testing:
     * php artisan absensi:tandai-alpha
     */
    protected $signature = 'absensi:tandai-alpha';

    protected $description = 'Menandai status Alpha untuk karyawan aktif yang tidak melakukan absen masuk sama sekali pada hari kerja.';

    public function handle(): int
    {
        $hariIni = Carbon::now(
            $this->timezone
        );

        /*
         * =================================================
         * JANGAN JALANKAN DI HARI LIBUR
         * =================================================
         *
         * Termasuk:
         * - Sabtu
         * - Minggu
         * - Hari libur khusus dari database
         */

        if ($this->hariLiburHariIni($hariIni)) {
            $this->info(
                'Hari ini hari libur, tidak ada yang ditandai Alpha.'
            );

            return self::SUCCESS;
        }

        $tanggalHariIni =
            $hariIni->toDateString();

        /*
         * =================================================
         * AMBIL KARYAWAN AKTIF
         * =================================================
         *
         * Hanya karyawan dengan status_aktif = 'aktif',
         * dan sudah melewati tanggal_masuk kerjanya
         * (atau tanggal_masuk belum diisi sama sekali).
         */

        $karyawanAktif =
            Karyawan::where(
                'status_aktif',
                'aktif'
            )
                ->where(function ($query) use ($tanggalHariIni) {
                    $query
                        ->whereNull('tanggal_masuk')
                        ->orWhereDate(
                            'tanggal_masuk',
                            '<=',
                            $tanggalHariIni
                        );
                })
                ->get();

        $jumlahDitandai = 0;

        foreach ($karyawanAktif as $karyawan) {
            $absenHariIni =
                Absensi::where(
                    'id_karyawan',
                    $karyawan->id_karyawan
                )
                    ->whereDate(
                        'tanggal',
                        $tanggalHariIni
                    )
                    ->first();

            /*
             * Kalau sudah ada record dan jam_masuk terisi,
             * berarti karyawan sudah absen -> lewati.
             */
            if (
                $absenHariIni &&
                $absenHariIni->jam_masuk
            ) {
                continue;
            }

            /*
             * Kalau sudah ada record berstatus Sakit atau
             * Izin (dari fitur pengajuan izin/sakit),
             * jangan ditimpa jadi Alpha.
             */
            if (
                $absenHariIni &&
                in_array(
                    $absenHariIni->status,
                    ['Sakit', 'Izin'],
                    true
                )
            ) {
                continue;
            }

            /*
             * =================================================
             * TANDAI ALPHA
             * =================================================
             *
             * Kalau belum ada record sama sekali hari ini,
             * dibuatkan barunya dengan status Alpha.
             *
             * Kalau sudah ada record tapi jam_masuk kosong
             * (kasus jarang, misal record dibuat manual),
             * statusnya ditimpa jadi Alpha.
             */

            Absensi::updateOrCreate(
                [
                    'id_karyawan' =>
                        $karyawan->id_karyawan,

                    'tanggal' =>
                        $tanggalHariIni,
                ],
                [
                    'status' =>
                        'Alpha',
                ]
            );

            $jumlahDitandai++;
        }

        $this->info(
            "Selesai. {$jumlahDitandai} karyawan ditandai Alpha untuk tanggal {$tanggalHariIni}."
        );

        return self::SUCCESS;
    }

    // =====================================================
    // CEK HARI LIBUR HARI INI
    // =====================================================
    //
    // Logic sama persis dengan yang dipakai di
    // AbsensiKaryawanController, supaya konsisten
    // (Sabtu, Minggu, dan hari libur dari database).

    protected function hariLiburHariIni(
        Carbon $hariIni
    ): bool {
        if ($hariIni->isSaturday()) {
            return true;
        }

        if ($hariIni->isSunday()) {
            return true;
        }

        return HariLibur::whereDate(
            'tanggal',
            $hariIni->toDateString()
        )->exists();
    }
}