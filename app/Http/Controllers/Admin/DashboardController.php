<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Penggajian;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $hariIni = Carbon::today();
        $bulanIni = Carbon::now()->month;
        $tahunIni = Carbon::now()->year;

        $jumlahAktif = Karyawan::where('status_aktif', 'aktif')->count();
        $jumlahNonaktif = Karyawan::where('status_aktif', 'nonaktif')->count();

        $totalHadirHariIni = Absensi::whereDate('tanggal', $hariIni)
            ->where('status', 'Hadir')
            ->count();

        $totalTidakHadirHariIni = Absensi::whereDate('tanggal', $hariIni)
            ->whereIn('status', ['Alpha', 'Sakit', 'Izin'])
            ->count();

       $lemburMenungguApproval = Absensi::where('jam_lembur', '>', 0)
    ->where('status_approval_lembur', 'pending')
    ->count();

        // Distribusi status absensi bulan berjalan, untuk pie chart
        $distribusiStatus = Absensi::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ringkasanPenggajian = [
            'bulan' => $bulanIni,
            'tahun' => $tahunIni,
            'final' => Penggajian::where('bulan', $bulanIni)->where('tahun', $tahunIni)->where('status', 'final')->count(),
            'draft' => Penggajian::where('bulan', $bulanIni)->where('tahun', $tahunIni)->where('status', 'draft')->count(),
        ];

        $aktivitasTerbaru = Absensi::with('karyawan:id_karyawan,nama')
            ->latest('tanggal')
            ->latest('id_absensi')
            ->limit(5)
            ->get(['id_absensi', 'id_karyawan', 'tanggal', 'status']);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'karyawanAktif' => $jumlahAktif,
                'karyawanNonaktif' => $jumlahNonaktif,
                'totalHadirHariIni' => $totalHadirHariIni,
                'totalTidakHadirHariIni' => $totalTidakHadirHariIni,
                'lemburMenungguApproval' => $lemburMenungguApproval,
            ],
            'distribusiStatus' => $distribusiStatus,
            'ringkasanPenggajian' => $ringkasanPenggajian,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }
}
