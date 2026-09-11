<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class HapusFotoAbsensiKedaluwarsa extends Command
{
    protected string $timezone = 'Asia/Jakarta';

    protected $signature = 'absensi:hapus-foto-kedaluwarsa';

    protected $description = 'Menghapus foto absensi dan surat pendukung Izin/Sakit yang sudah lebih dari 48 jam.';

    public function handle(): int
    {
        $batasWaktu = Carbon::now(
            $this->timezone
        )->subHours(48);

        $kandidat =
            Absensi::query()
                ->where(function ($query) {
                    $query
                        ->whereNotNull('foto_masuk')
                        ->orWhereNotNull('foto_pulang')
                        ->orWhereNotNull('file_pendukung');
                })
                ->whereDate(
                    'tanggal',
                    '<=',
                    Carbon::now(
                        $this->timezone
                    )->toDateString()
                )
                ->get();

        $disk = Storage::disk('public');

        $jumlahFotoMasukDihapus = 0;
        $jumlahFotoPulangDihapus = 0;
        $jumlahSuratDihapus = 0;

        foreach ($kandidat as $absensi) {
            $tanggal =
                Carbon::parse(
                    $absensi->tanggal
                )->toDateString();

            $adaPerubahan = false;

           

            if (
                $absensi->foto_masuk &&
                $absensi->jam_masuk
            ) {
                $waktuMasuk =
                    Carbon::parse(
                        $tanggal . ' ' . $absensi->jam_masuk,
                        $this->timezone
                    );

                if ($waktuMasuk->lt($batasWaktu)) {
                    if ($disk->exists($absensi->foto_masuk)) {
                        $disk->delete(
                            $absensi->foto_masuk
                        );
                    }

                    $absensi->foto_masuk = null;

                    $adaPerubahan = true;

                    $jumlahFotoMasukDihapus++;
                }
            }

            

            if (
                $absensi->foto_pulang &&
                $absensi->jam_pulang
            ) {
                $waktuPulang =
                    Carbon::parse(
                        $tanggal . ' ' . $absensi->jam_pulang,
                        $this->timezone
                    );

                if ($waktuPulang->lt($batasWaktu)) {
                    if ($disk->exists($absensi->foto_pulang)) {
                        $disk->delete(
                            $absensi->foto_pulang
                        );
                    }

                    $absensi->foto_pulang = null;

                    $adaPerubahan = true;

                    $jumlahFotoPulangDihapus++;
                }
            }


            if ($absensi->file_pendukung) {
                if (
                    $disk->exists($absensi->file_pendukung)
                ) {
                    $waktuFile =
                        Carbon::createFromTimestamp(
                            $disk->lastModified(
                                $absensi->file_pendukung
                            ),
                            $this->timezone
                        );

                    if ($waktuFile->lt($batasWaktu)) {
                        $disk->delete(
                            $absensi->file_pendukung
                        );

                        $absensi->file_pendukung = null;

                        $adaPerubahan = true;

                        $jumlahSuratDihapus++;
                    }
                } else {
                   
                    $absensi->file_pendukung = null;

                    $adaPerubahan = true;
                }
            }


            if ($adaPerubahan) {
                $absensi->save();
            }
        }

        $this->info(
            "Selesai. {$jumlahFotoMasukDihapus} foto masuk, " .
            "{$jumlahFotoPulangDihapus} foto pulang, dan " .
            "{$jumlahSuratDihapus} surat pendukung dihapus."
        );

        return self::SUCCESS;
    }
}