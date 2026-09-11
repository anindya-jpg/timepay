<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JabatanController extends Controller
{
    public function index(): Response
    {
        $jabatans = Jabatan::withCount('karyawans')
            ->when(request('search'), function ($query, $search) {
                $query->where('nama_jabatan', 'like', "%{$search}%");
            })
            ->orderBy('nama_jabatan')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/jabatan/index', [
            'jabatans' => $jabatans,
            'filters' => request()->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_jabatan' => ['required', 'string', 'max:100'],
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
        ]);

        Jabatan::create($data);

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $data = $request->validate([
            'nama_jabatan' => ['required', 'string', 'max:100'],
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
        ]);

        $jabatan->update($data);

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan): RedirectResponse
    {
        if ($jabatan->karyawans()->exists()) {
            return back()->withErrors(['jabatan' => 'Jabatan masih dipakai karyawan, tidak bisa dihapus.']);
        }

        $jabatan->delete();

        return back()->with('success', 'Jabatan berhasil dihapus.');
    }
}
