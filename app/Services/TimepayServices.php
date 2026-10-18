<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PotonganRule;
use App\Models\TarifLembur;
use App\Models\Tunjangan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimepayServices
{
    /**
     * Generate draft penggajian untuk semua karyawan aktif pada bulan & tahun tertentu.
     * Kalau draft untuk periode itu sudah ada, akan di-replace (bukan dobel).
     */
    public function generate(int $bulan, int $tahun): Collection
    {
        $tarifLembur = (float) (TarifLembur::latest('id_tarif')->first()?->tarif_per_jam ?? 0);
        $potonganRules = PotonganRule::all()->keyBy('nama_potongan');

        return Karyawan::where('status_aktif', 'aktif')
            ->get()
            ->map(function (Karyawan $karyawan) use ($bulan, $tahun, $tarifLembur, $potonganRules) {
                return $this->generateUntukKaryawan($karyawan, $bulan, $tahun, $tarifLembur, $potonganRules);
            });
    }

    protected function generateUntukKaryawan(
        Karyawan $karyawan,
        int $bulan,
        int $tahun,
        float $tarifLembur,
        Collection $potonganRules
    ): Penggajian {
        $absensiBulanIni = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        $jumlahHadir = $absensiBulanIni->whereIn('status', ['Hadir', 'Telat', 'Pulang Cepat'])->count();
        $jumlahTelat = $absensiBulanIni->where('status', 'Telat')->count();
        $jumlahAlpha = $absensiBulanIni->where('status', 'Alpha')->count();
        $jumlahSakit = $absensiBulanIni->where('status', 'Sakit')->count();
        $jumlahIzin = $absensiBulanIni->where('status', 'Izin')->count();
        $jumlahPulangCepat = $absensiBulanIni->where('status', 'Pulang Cepat')->count();
        $totalMenitTelat = $absensiBulanIni->sum('menit_telat');

        $totalJamLembur = (float) $absensiBulanIni
            ->where('status_approval_lembur', 'approved')
            ->sum('jam_lembur');

        $gajiPokok = (float) $karyawan->jabatan->gaji_pokok;

        $totalTunjangan = (float) $karyawan->jabatan
            ->tunjangans()
            ->sum('jabatan_tunjangan.nominal');

        $totalTunjanganLembur = $totalJamLembur * $tarifLembur;

        $totalPotongan = $this->hitungPotongan(
            $potonganRules,
            $gajiPokok,
            $totalMenitTelat,
            $jumlahPulangCepat,
            $jumlahAlpha,
            $jumlahIzin,
            $jumlahSakit
        );

        $gajiBersih = $gajiPokok + $totalTunjangan + $totalTunjanganLembur - $totalPotongan;

        return Penggajian::updateOrCreate(
            [
                'id_karyawan' => $karyawan->id_karyawan,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            [
                'gaji_pokok' => $gajiPokok,
                'total_tunjangan' => $totalTunjangan,
                'total_jam_lembur' => $totalJamLembur,
                'total_tunjangan_lembur' => $totalTunjanganLembur,
                'total_potongan' => $totalPotongan,
                'jumlah_hadir' => $jumlahHadir,
                'jumlah_telat' => $jumlahTelat,
                'jumlah_alpha' => $jumlahAlpha,
                'gaji_bersih' => max($gajiBersih, 0),
                'tanggal_generate' => Carbon::now(),
                'status' => 'draft',
            ]
        );
    }

    /**
     * Hitung total potongan berdasarkan aturan di tabel potongan_rule.
     * Jenis: per_menit (dikali total menit telat), nominal_tetap (dikali jumlah kejadian),
     * persen (dari gaji pokok, dikali jumlah kejadian), per_hari (belum ada nominal baku, default 0).
     */
    protected function hitungPotongan(
        Collection $rules,
        float $gajiPokok,
        int $totalMenitTelat,
        int $jumlahPulangCepat,
        int $jumlahAlpha,
        int $jumlahIzin,
        int $jumlahSakit
    ): float {
        $total = collect($this->rincianPotongan(
            $rules, $gajiPokok, $totalMenitTelat, $jumlahPulangCepat, $jumlahAlpha, $jumlahIzin, $jumlahSakit
        ))->sum('nominal');

        return round($total, 2);
    }

    /**
     * Sama seperti hitungPotongan(), tapi mengembalikan rincian per item
     * (dipakai untuk slip gaji PDF), bukan cuma totalnya.
     */
    protected function rincianPotongan(
        Collection $rules,
        float $gajiPokok,
        int $totalMenitTelat,
        int $jumlahPulangCepat,
        int $jumlahAlpha,
        int $jumlahIzin,
        int $jumlahSakit
    ): array {
        $rincian = [];

        $kejadian = [
            'Telat' => $totalMenitTelat,
            'Pulang Cepat' => $jumlahPulangCepat,
            'Alpha' => $jumlahAlpha,
            'Izin' => $jumlahIzin,
            'Sakit' => $jumlahSakit,
        ];

        foreach ($kejadian as $nama => $jumlahKejadian) {
            if ($rule = $rules->get($nama)) {
                $nominal = $this->hitungNominalPotongan($rule, $gajiPokok, $jumlahKejadian);
                if ($nominal > 0) {
                    $rincian[] = ['nama' => $nama, 'nominal' => $nominal];
                }
            }
        }

        // Potongan wajib seperti BPJS JHT / BPJS JP, berlaku tiap bulan (persen dari gaji pokok)
        foreach ($rules->where('jenis', 'persen') as $namaPotongan => $rule) {
            if (in_array($namaPotongan, ['Alpha', 'Izin'], true)) {
                continue; // sudah dihitung di atas berdasar jumlah kejadian
            }

            if (str_contains($namaPotongan, 'BPJS')) {
                $rincian[] = [
                    'nama' => $namaPotongan,
                    'nominal' => $gajiPokok * ((float) $rule->nominal / 100),
                ];
            }
        }

        return $rincian;
    }

    protected function hitungNominalPotongan(PotonganRule $rule, float $gajiPokok, int $jumlahKejadian): float
    {
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

    /**
     * Finalisasi semua draft penggajian di periode tertentu jadi 'final'
     * (sesuai tombol "Finalisasi Semua" di wireframe).
     */
    public function finalisasi(int $bulan, int $tahun): int
    {
        return Penggajian::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'draft')
            ->update(['status' => 'final']);
    }

    /**
     * Data rincian untuk slip gaji PDF: rincian tunjangan, rincian potongan,
     * dan statistik kehadiran yang tidak disimpan sebagai kolom di tabel penggajian.
     */
    public function getRincianSlip(Penggajian $penggajian): array
    {
        $karyawan = $penggajian->karyawan;

        $rincianTunjangan = $karyawan->jabatan->tunjangans()
            ->get()
            ->map(fn (Tunjangan $t) => [
                'nama' => $t->nama_tunjangan,
                'nominal' => (float) $t->pivot->nominal,
            ])
            ->all();

        $absensiBulanIni = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->whereMonth('tanggal', $penggajian->bulan)
            ->whereYear('tanggal', $penggajian->tahun)
            ->get();

        $jumlahIzin = $absensiBulanIni->where('status', 'Izin')->count();
        $jumlahSakit = $absensiBulanIni->where('status', 'Sakit')->count();
        $jumlahPulangCepat = $absensiBulanIni->where('status', 'Pulang Cepat')->count();
        $jumlahAlpha = $absensiBulanIni->where('status', 'Alpha')->count();
        $totalMenitTelat = $absensiBulanIni->sum('menit_telat');

        $potonganRules = PotonganRule::all()->keyBy('nama_potongan');

        $rincianPotongan = $this->rincianPotongan(
            $potonganRules,
            (float) $penggajian->gaji_pokok,
            $totalMenitTelat,
            $jumlahPulangCepat,
            $jumlahAlpha,
            $jumlahIzin,
            $jumlahSakit
        );

        $totalPendapatan = (float) $penggajian->gaji_pokok
            + (float) $penggajian->total_tunjangan
            + (float) $penggajian->total_tunjangan_lembur;

        return [
            'rincianTunjangan' => $rincianTunjangan,
            'rincianPotongan' => $rincianPotongan,
            'totalPendapatan' => $totalPendapatan,
            'jumlahIzin' => $jumlahIzin,
            'jumlahSakit' => $jumlahSakit,
            'jumlahPulangCepat' => $jumlahPulangCepat,
        ];
    }
}