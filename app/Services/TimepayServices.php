<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Models\PotonganRule;
use App\Models\TarifLembur;
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
        $total = 0.0;

        if ($rule = $rules->get('Telat')) {
            $total += $this->hitungNominalPotongan($rule, $gajiPokok, $totalMenitTelat);
        }

        if ($rule = $rules->get('Pulang Cepat')) {
            $total += $this->hitungNominalPotongan($rule, $gajiPokok, $jumlahPulangCepat);
        }

        if ($rule = $rules->get('Alpha')) {
            $total += $this->hitungNominalPotongan($rule, $gajiPokok, $jumlahAlpha);
        }

        if ($rule = $rules->get('Izin')) {
            $total += $this->hitungNominalPotongan($rule, $gajiPokok, $jumlahIzin);
        }

        if ($rule = $rules->get('Sakit')) {
            $total += $this->hitungNominalPotongan($rule, $gajiPokok, $jumlahSakit);
        }

        // Potongan wajib seperti BPJS JHT / BPJS JP, berlaku tiap bulan (persen dari gaji pokok)
        foreach ($rules->where('jenis', 'persen') as $namaPotongan => $rule) {
            if (in_array($namaPotongan, ['Alpha', 'Izin'], true)) {
                continue; // sudah dihitung di atas berdasar jumlah kejadian
            }

            if (str_contains($namaPotongan, 'BPJS')) {
                $total += $gajiPokok * ((float) $rule->nominal / 100);
            }
        }

        return round($total, 2);
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
}