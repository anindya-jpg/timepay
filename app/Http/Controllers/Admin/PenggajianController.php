<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penggajian;
use App\Services\TimepayServices;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PenggajianController extends Controller
{
    public function __construct(protected TimepayServices $payrollService) {}

    protected array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $penggajians = Penggajian::with('karyawan:id_karyawan,nama,nip')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        return Inertia::render('admin/penggajian/index', [
            'penggajians' => $penggajians,
            'filters' => ['bulan' => $bulan, 'tahun' => $tahun],
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020'],
        ]);

        $this->payrollService->generate($data['bulan'], $data['tahun']);

        return back()->with('success', 'Gaji berhasil digenerate sebagai draft.');
    }

    public function finalisasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020'],
        ]);

        $this->payrollService->finalisasi($data['bulan'], $data['tahun']);

        return back()->with('success', 'Penggajian berhasil difinalisasi.');
    }

    public function show(Penggajian $penggajian): Response
    {
        $penggajian->load('karyawan.jabatan');

        return Inertia::render('admin/penggajian/show', [
            'penggajian' => $penggajian,
        ]);
    }

    public function rekapPdf(Request $request): HttpResponse
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $penggajians = Penggajian::with('karyawan.jabatan')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->orderBy('id_karyawan')
            ->get();

        $namaBulan = $this->namaBulan[$bulan];

        $pdf = Pdf::loadView('pdf.rekap-gaji', [
            'penggajians' => $penggajians,
            'bulan' => $namaBulan,
            'tahun' => $tahun,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("rekap-gaji-{$namaBulan}-{$tahun}.pdf");
    }

        public function slipPdf(Penggajian $penggajian): HttpResponse
    {
        $penggajian->load('karyawan.jabatan');

        $rincian = $this->payrollService->getRincianSlip($penggajian);

        $pdf = Pdf::loadView('pdf.slip-gaji', array_merge([
            'penggajian' => $penggajian,
            'namaBulan' => $this->namaBulan[$penggajian->bulan],
        ], $rincian));

        $namaFile = "slip-gaji-{$penggajian->karyawan->nama}-{$this->namaBulan[$penggajian->bulan]}-{$penggajian->tahun}.pdf";

        return $pdf->download($namaFile);
    }
}