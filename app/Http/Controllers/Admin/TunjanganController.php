<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\JabatanTunjangan;
use App\Models\Tunjangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TunjanganController extends Controller
{
    /**
     * Tab "Daftar Tunjangan" — master data jenis tunjangan.
     */
    public function index(): Response
    {
        $tunjangans = Tunjangan::when(request('search'), function ($query, $search) {
            $query->where('nama_tunjangan', 'like', "%{$search}%");
        })
            ->orderBy('nama_tunjangan')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/tunjangan/index', [
            'tunjangans' => $tunjangans,
            'filters' => request()->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_tunjangan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        Tunjangan::create($data);

        return back()->with('success', 'Tunjangan berhasil ditambahkan.');
    }

    public function update(Request $request, Tunjangan $tunjangan): RedirectResponse
    {
        $data = $request->validate([
            'nama_tunjangan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $tunjangan->update($data);

        return back()->with('success', 'Tunjangan berhasil diperbarui.');
    }

    public function destroy(Tunjangan $tunjangan): RedirectResponse
    {
        $tunjangan->delete();

        return back()->with('success', 'Tunjangan berhasil dihapus.');
    }

    /**
     * Tab "Atur Nominal per Jabatan" — kelola tabel pivot jabatan_tunjangan.
     */
    public function nominalIndex(): Response
    {
        $nominals = JabatanTunjangan::with(['jabatan:id_jabatan,nama_jabatan', 'tunjangan:id_tunjangan,nama_tunjangan'])
            ->when(request('id_jabatan'), fn ($query, $id) => $query->where('id_jabatan', $id))
            ->when(request('search'), function ($query, $search) {
                $query->whereHas('tunjangan', fn ($q) => $q->where('nama_tunjangan', 'like', "%{$search}%"));
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/tunjangan/nominal', [
            'nominals' => $nominals,
            'jabatans' => Jabatan::select('id_jabatan', 'nama_jabatan')->orderBy('nama_jabatan')->get(),
            'tunjangans' => Tunjangan::select('id_tunjangan', 'nama_tunjangan')->orderBy('nama_tunjangan')->get(),
            'filters' => request()->only(['search', 'id_jabatan']),
        ]);
    }

    public function nominalStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_jabatan' => ['required', 'exists:jabatan,id_jabatan'],
            'id_tunjangan' => ['required', 'exists:tunjangan,id_tunjangan'],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        JabatanTunjangan::updateOrCreate(
            ['id_jabatan' => $data['id_jabatan'], 'id_tunjangan' => $data['id_tunjangan']],
            ['nominal' => $data['nominal']]
        );

        return back()->with('success', 'Nominal tunjangan berhasil disimpan.');
    }

    public function nominalUpdate(Request $request, JabatanTunjangan $jabatanTunjangan): RedirectResponse
    {
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $jabatanTunjangan->update($data);

        return back()->with('success', 'Nominal tunjangan berhasil diperbarui.');
    }

    public function nominalDestroy(JabatanTunjangan $jabatanTunjangan): RedirectResponse
    {
        $jabatanTunjangan->delete();

        return back()->with('success', 'Data berhasil dihapus.');
    }
}
