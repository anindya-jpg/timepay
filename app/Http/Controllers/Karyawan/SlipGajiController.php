<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Penggajian;
use App\Models\PotonganRule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SlipGajiController extends Controller
{
    protected array $namaBulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function index(): Response
    {
        $karyawan = Auth::guard('karyawan')->user();

        $periodeTersedia = Penggajian::where(
            'id_karyawan',
            $karyawan->id_karyawan
        )
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get(['bulan', 'tahun'])
            ->map(fn ($p) => [
                'bulan' => $p->bulan,
                'tahun' => $p->tahun,
                'label' => "{$this->namaBulan[$p->bulan]} {$p->tahun}",
            ]);

        $bulan = (int) request(
            'bulan',
            $periodeTersedia->first()['bulan'] ?? now()->month
        );

        $tahun = (int) request(
            'tahun',
            $periodeTersedia->first()['tahun'] ?? now()->year
        );

        $penggajian = Penggajian::with([
            'karyawan.jabatan.tunjangans',
        ])
            ->where('id_karyawan', $karyawan->id_karyawan)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if (!$penggajian) {
            return Inertia::render('karyawan/slip-gaji/index', [
                'penggajian' => null,
                'jumlahIzin' => 0,
                'periodeTersedia' => $periodeTersedia,
                'filters' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                'rincianTunjangan' => [],
                'rincianPotongan' => [],
                'totalPendapatan' => 0,
            ]);
        }

        $absensi = $this->hitungAbsensi(
            $karyawan->id_karyawan,
            $bulan,
            $tahun
        );

        $rincian = $this->hitungRincianGaji($penggajian, $absensi);

        return Inertia::render('karyawan/slip-gaji/index', [
            'penggajian' => $penggajian,
            'jumlahIzin' => $absensi['jumlahIzin'],
            'periodeTersedia' => $periodeTersedia,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            'rincianTunjangan' => $rincian['rincianTunjangan'],
            'rincianPotongan' => $rincian['rincianPotongan'],
            'totalPendapatan' => $rincian['totalPendapatan'],
        ]);
    }

    protected function hitungAbsensi(
        int $idKaryawan,
        int $bulan,
        int $tahun
    ): array {
        $absensi = Absensi::where('id_karyawan', $idKaryawan)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        return [
            'jumlahIzin' => $absensi->where('status', 'Izin')->count(),
            'jumlahTelat' => $absensi->where('status', 'Telat')->count(),
            'jumlahAlpha' => $absensi->where('status', 'Alpha')->count(),
            'jumlahSakit' => $absensi->where('status', 'Sakit')->count(),
            'jumlahPulangCepat' => $absensi->where('status', 'Pulang Cepat')->count(),
            'totalMenitTelat' => (int) $absensi->sum('menit_telat'),
        ];
    }

    protected function hitungRincianGaji(
        Penggajian $penggajian,
        array $absensi
    ): array {
        $rincianTunjangan = $penggajian
            ->karyawan
            ->jabatan
            ->tunjangans
            ->map(function ($tunjangan) {
                return [
                    'nama' => $tunjangan->nama_tunjangan,
                    'nominal' => (float) $tunjangan->pivot->nominal,
                ];
            })
            ->values();

        $rules = PotonganRule::all()->keyBy('nama_potongan');
        $gajiPokok = (float) $penggajian->gaji_pokok;
        $rincianPotongan = [];

        if ($rule = $rules->get('Telat')) {
            $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $absensi['totalMenitTelat']);
            $rincianPotongan[] = [
                'nama' => 'Potongan Telat',
                'keterangan' => $absensi['totalMenitTelat'] . ' menit',
                'nominal' => $nominal,
            ];
        }

        if ($rule = $rules->get('Pulang Cepat')) {
            $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $absensi['jumlahPulangCepat']);
            $rincianPotongan[] = [
                'nama' => 'Potongan Pulang Cepat',
                'keterangan' => $absensi['jumlahPulangCepat'] . ' kali',
                'nominal' => $nominal,
            ];
        }

        if ($rule = $rules->get('Izin')) {
            $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $absensi['jumlahIzin']);
            $rincianPotongan[] = [
                'nama' => 'Potongan Izin',
                'keterangan' => $absensi['jumlahIzin'] . ' hari',
                'nominal' => $nominal,
            ];
        }

        if ($rule = $rules->get('Alpha')) {
            $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $absensi['jumlahAlpha']);
            $rincianPotongan[] = [
                'nama' => 'Potongan Alpha',
                'keterangan' => $absensi['jumlahAlpha'] . ' hari',
                'nominal' => $nominal,
            ];
        }

        if ($rule = $rules->get('Sakit')) {
            $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $absensi['jumlahSakit']);
            $rincianPotongan[] = [
                'nama' => 'Potongan Sakit',
                'keterangan' => $absensi['jumlahSakit'] . ' hari',
                'nominal' => $nominal,
            ];
        }

        foreach ($rules->where('jenis', 'persen') as $namaPotongan => $rule) {
            if (in_array($namaPotongan, ['Alpha', 'Izin'], true)) {
                continue;
            }

            if (str_contains($namaPotongan, 'BPJS')) {
                $nominal = $gajiPokok * ((float) $rule->nominal / 100);
                $rincianPotongan[] = [
                    'nama' => $namaPotongan,
                    'keterangan' => $rule->nominal . '% dari gaji pokok',
                    'nominal' => round($nominal, 2),
                ];
            }
        }

        $totalTunjangan = $rincianTunjangan->sum('nominal');

        $totalPendapatan =
            $gajiPokok +
            $totalTunjangan +
            (float) $penggajian->total_tunjangan_lembur;

        return [
            'rincianTunjangan' => $rincianTunjangan,
            'rincianPotongan' => $rincianPotongan,
            'totalPendapatan' => $totalPendapatan,
        ];
    }

    protected function hitungNominalPotongan(
        PotonganRule $rule,
        float $gajiPokok,
        int $jumlahKejadian
    ): float {
        if ($jumlahKejadian <= 0) {
            return 0.0;
        }

        return match ($rule->jenis) {
            'per_menit' => (float) $rule->nominal * $jumlahKejadian,
            'nominal_tetap' => (float) $rule->nominal * $jumlahKejadian,
            'persen' => ($gajiPokok * ((float) $rule->nominal / 100)) * $jumlahKejadian,
            'per_hari' => (float) $rule->nominal * $jumlahKejadian,
            default => 0.0,
        };
    }

    protected function terbilang(float $angka): string
    {
        $angka = (int) floor($angka);

        $satuan = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan',
            'Sepuluh', 'Sebelas',
        ];

        $baca = function (int $n) use (&$baca, $satuan): string {
            if ($n < 12) {
                return $satuan[$n];
            }
            if ($n < 20) {
                return trim($baca($n - 10) . ' Belas');
            }
            if ($n < 100) {
                return trim($baca(intdiv($n, 10)) . ' Puluh ' . $baca($n % 10));
            }
            if ($n < 200) {
                return trim('Seratus ' . $baca($n - 100));
            }
            if ($n < 1000) {
                return trim($baca(intdiv($n, 100)) . ' Ratus ' . $baca($n % 100));
            }
            if ($n < 2000) {
                return trim('Seribu ' . $baca($n - 1000));
            }
            if ($n < 1000000) {
                return trim($baca(intdiv($n, 1000)) . ' Ribu ' . $baca($n % 1000));
            }
            if ($n < 1000000000) {
                return trim($baca(intdiv($n, 1000000)) . ' Juta ' . $baca($n % 1000000));
            }
            return trim($baca(intdiv($n, 1000000000)) . ' Miliar ' . $baca($n % 1000000000));
        };

        return trim(preg_replace('/\s+/', ' ', $baca($angka)));
    }

    public function pdf(): HttpResponse
    {
        $karyawan = Auth::guard('karyawan')->user();

        $bulan = (int) request('bulan', now()->month);
        $tahun = (int) request('tahun', now()->year);

        $penggajian = Penggajian::with([
            'karyawan.jabatan.tunjangans',
        ])
            ->where('id_karyawan', $karyawan->id_karyawan)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->firstOrFail();

        $absensi = $this->hitungAbsensi(
            $karyawan->id_karyawan,
            $bulan,
            $tahun
        );

        $rincian = $this->hitungRincianGaji($penggajian, $absensi);

        $pdf = Pdf::loadView('pdf.slip-gaji', [
            'penggajian' => $penggajian,
            'namaBulan' => $this->namaBulan[$penggajian->bulan],

            'jumlahIzin' => $absensi['jumlahIzin'],
            'jumlahSakit' => $absensi['jumlahSakit'],
            'jumlahPulangCepat' => $absensi['jumlahPulangCepat'],
            'rincianTunjangan' => $rincian['rincianTunjangan'],
            'rincianPotongan' => $rincian['rincianPotongan'],
            'totalPendapatan' => $rincian['totalPendapatan'],
            'terbilang' => $this->terbilang($penggajian->gaji_bersih),
        ]);

        return $pdf->download(
            "slip-gaji-{$this->namaBulan[$bulan]}-{$tahun}.pdf"
        );
    }
}