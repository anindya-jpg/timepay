<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Penggajian;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $karyawan = Auth::guard('karyawan')
            ->user()
            ->load('jabatan');

        $bulan = (int) request('bulan', now()->month);
        $tahun = (int) request('tahun', now()->year);

        /*
        |--------------------------------------------------------------------------
        | ABSENSI PERIODE YANG DIPILIH
        |--------------------------------------------------------------------------
        */
        $absensiPeriode = Absensi::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN KEHADIRAN
        |--------------------------------------------------------------------------
        */
        $ringkasanKehadiran = [
            'rata_rata_jam_masuk' => $this->rataRataJam(
                $absensiPeriode
                    ->pluck('jam_masuk')
                    ->filter(function ($jam) {
                        return !empty($jam);
                    })
            ),

            'rata_rata_jam_pulang' => $this->rataRataJam(
                $absensiPeriode
                    ->pluck('jam_pulang')
                    ->filter(function ($jam) {
                        return !empty($jam);
                    })
            ),

            'total_menit_telat' => (int) $absensiPeriode->sum(
                'menit_telat'
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | PENGGAJIAN
        |--------------------------------------------------------------------------
        */
        $penggajian = Penggajian::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | RIWAYAT ABSENSI TERAKHIR
        |--------------------------------------------------------------------------
        */
        $riwayatTerakhir = Absensi::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->orderByDesc('tanggal')
            ->limit(4)
            ->get([
                'id_absensi',
                'tanggal',
                'jam_masuk',
                'jam_pulang',
                'menit_telat',
                'jam_lembur',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | JADWAL CUTI / HARI LIBUR
        |--------------------------------------------------------------------------
        */
        $jadwalCuti = HariLibur::whereDate(
            'tanggal',
            '>=',
            now()->toDateString()
        )
            ->orderBy('tanggal')
            ->limit(3)
            ->get([
                'id_libur',
                'tanggal',
                'keterangan',
            ]);

        /*
        |--------------------------------------------------------------------------
        | KIRIM KE INERTIA
        |--------------------------------------------------------------------------
        */
        return Inertia::render('karyawan/Dashboard', [
            'karyawan' => $karyawan,

            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],

            'ringkasanKehadiran' => $ringkasanKehadiran,

            'penggajian' => $penggajian,

            'riwayatTerakhir' => $riwayatTerakhir,

            'jadwalCuti' => $jadwalCuti,
        ]);
    }

    /**
     * Menghitung rata-rata waktu dari kumpulan jam.
     *
     * Contoh:
     *
     * 08:00
     * 08:30
     * 09:00
     *
     * Rata-rata = 08:30
     *
     * Input:
     * Collection berisi format H:i atau H:i:s
     *
     * Output:
     * H:i
     */
    protected function rataRataJam($daftarJam): ?string
    {
        if ($daftarJam->isEmpty()) {
            return null;
        }

        $totalDetik = 0;
        $jumlahValid = 0;

        foreach ($daftarJam as $jam) {
            if (!$jam) {
                continue;
            }

          
            $bagian = explode(':', trim((string) $jam));

            if (count($bagian) < 2) {
                continue;
            }

            $jamAngka = (int) $bagian[0];
            $menit = (int) $bagian[1];
            $detik = isset($bagian[2])
                ? (int) $bagian[2]
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Validasi waktu
            |--------------------------------------------------------------------------
            */
            if (
                $jamAngka < 0 ||
                $jamAngka > 23 ||
                $menit < 0 ||
                $menit > 59 ||
                $detik < 0 ||
                $detik > 59
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Ubah menjadi total detik sejak 00:00
            |--------------------------------------------------------------------------
            */
            $totalDetik += (
                ($jamAngka * 3600) +
                ($menit * 60) +
                $detik
            );

            $jumlahValid++;
        }

        /*
        |--------------------------------------------------------------------------
        | Tidak ada waktu yang valid
        |--------------------------------------------------------------------------
        */
        if ($jumlahValid === 0) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Hitung rata-rata
        |--------------------------------------------------------------------------
        */
        $rataRataDetik = (int) round(
            $totalDetik / $jumlahValid
        );

        /*
        |--------------------------------------------------------------------------
        | Ubah kembali menjadi HH:MM
        |--------------------------------------------------------------------------
        */
        $jamRataRata = intdiv(
            $rataRataDetik,
            3600
        );

        $sisaDetik = $rataRataDetik % 3600;

        $menitRataRata = intdiv(
            $sisaDetik,
            60
        );

        return sprintf(
            '%02d:%02d',
            $jamRataRata,
            $menitRataRata
        );
    }
}