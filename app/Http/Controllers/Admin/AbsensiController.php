<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\PengaturanJamKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiController extends Controller
{
    public function index(): Response
    {
        $absensis = Absensi::with('karyawan:id_karyawan,nama,nip')
            ->when(request('search'), function ($query, $search) {
                $query->whereHas('karyawan', fn ($q) => $q->where('nama', 'like', "%{$search}%"));
            })
            ->when(request('bulan'), function ($query, $bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/absensi/index', [
            'absensis' => $absensis,
            'karyawans' => Karyawan::select('id_karyawan', 'nama', 'nip')->orderBy('nama')->get(),
            'filters' => request()->only(['search', 'bulan', 'status']),
        ]);
    }

    /**
     * Admin bisa input/koreksi manual absensi karyawan.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_karyawan' => ['required', 'exists:karyawan,id_karyawan'],
            'tanggal' => ['required', 'date', Rule::unique('absensi')->where(fn ($q) => $q->where('id_karyawan', $request->id_karyawan))],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_pulang' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(['Hadir', 'Telat', 'Pulang Cepat', 'Alpha', 'Sakit', 'Izin'])],
            'menit_telat' => ['nullable', 'integer', 'min:0'],
        ]);

        $data = $this->lengkapiJamLembur($data);

        Absensi::create($data);

        return back()->with('success', 'Absensi berhasil ditambahkan.');
    }

    public function update(Request $request, Absensi $absensi): RedirectResponse
    {
        $data = $request->validate([
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_pulang' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(['Hadir', 'Telat', 'Pulang Cepat', 'Alpha', 'Sakit', 'Izin'])],
            'menit_telat' => ['nullable', 'integer', 'min:0'],
        ]);

        $jamLemburLama = (float) $absensi->jam_lembur;

        $data = $this->lengkapiJamLembur($data);

        // Kalau jam lembur berubah (misal admin ubah jam pulang), minta approval ulang.
        if ((float) $data['jam_lembur'] !== $jamLemburLama) {
            $data['status_approval_lembur'] = 'pending';
        }

        $absensi->update($data);

        return back()->with('success', 'Absensi berhasil diperbarui.');
    }

    public function destroy(Absensi $absensi): RedirectResponse
    {
        $absensi->delete();

        return back()->with('success', 'Absensi berhasil dihapus.');
    }

    /**
     * Hitung otomatis jam_lembur dari selisih jam_pulang ke jam_pulang_standar
     * (menu Pengaturan Jam Kerja). Kalau jam_pulang lebih besar dari standar,
     * selisihnya (dalam jam, desimal) dicatat sebagai jam_lembur.
     */
    protected function lengkapiJamLembur(array $data): array
    {
        $data['jam_lembur'] = 0;

        if (empty($data['jam_pulang'])) {
            return $data;
        }

        $standar = PengaturanJamKerja::first();

        if (! $standar || empty($standar->jam_pulang_standar)) {
            return $data;
        }

        $jamPulang = Carbon::parse($data['jam_pulang']);
        $jamPulangStandar = Carbon::parse($standar->jam_pulang_standar);

        $selisihMenit = $jamPulang->diffInMinutes($jamPulangStandar, false) * -1;

        if ($selisihMenit > 0) {
            $data['jam_lembur'] = round($selisihMenit / 60, 2);
        }

        return $data;
    }
}