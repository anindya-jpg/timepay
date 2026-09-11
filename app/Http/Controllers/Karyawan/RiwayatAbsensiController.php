<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatAbsensiController extends Controller
{
    public function index(): Response
    {
        $karyawan = Auth::guard('karyawan')->user();

        $bulan = request('bulan');
        $tahun = request('tahun');

        $riwayat = Absensi::where('id_karyawan', $karyawan->id_karyawan)
            ->when($bulan, fn ($query, $bulan) => $query->whereMonth('tanggal', $bulan))
            ->when($tahun, fn ($query, $tahun) => $query->whereYear('tanggal', $tahun))
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('karyawan/riwayat-absensi/index', [
            'riwayat' => $riwayat,
            'filters' => [
                'bulan' => $bulan ? (int) $bulan : null,
                'tahun' => $tahun ? (int) $tahun : null,
            ],
        ]);
    }
}