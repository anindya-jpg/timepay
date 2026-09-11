<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalLemburController extends Controller
{
   public function index(): Response
    {
        $lemburs = Absensi::with('karyawan:id_karyawan,nama,nip')
            ->where('jam_lembur', '>', 0)
            ->when(request('search'), function ($query, $search) {
                $query->whereHas('karyawan', fn ($q) => $q->where('nama', 'like', "%{$search}%"));
            })
            ->when(request('tahun'), function ($query, $tahun) {
                $query->whereYear('tanggal', $tahun);
            })
            ->when(request('bulan'), function ($query, $bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->when(request('status'), fn ($query, $status) => $query->where('status_approval_lembur', $status))
            ->orderByDesc('tanggal')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/approval-lembur/index', [
            'lemburs' => $lemburs,
            'filters' => request()->only(['search', 'tahun', 'bulan', 'status']),
        ]);
    }

    /**
     * Koreksi manual jam lembur / keterangan, dipakai kalau ada salah catat.
     * Setelah dikoreksi, tinggal Generate Gaji ulang di menu Penggajian
     * biar perubahan ini ikut kehitung.
     */
    public function update(Request $request, Absensi $absensi): RedirectResponse
    {
        $data = $request->validate([
            'jam_lembur' => ['required', 'numeric', 'min:0', 'max:24'],
            'keterangan_lembur' => ['nullable', 'string', 'max:255'],
        ]);

        $absensi->update($data);

        return back()->with('success', 'Data lembur berhasil diperbarui.');
    }

    public function approve(Absensi $absensi): RedirectResponse
    {
        $absensi->update(['status_approval_lembur' => 'approved']);

        return back()->with('success', 'Lembur disetujui.');
    }

    public function reject(Absensi $absensi): RedirectResponse
    {
        $absensi->update(['status_approval_lembur' => 'rejected']);

        return back()->with('success', 'Lembur ditolak.');
    }
}