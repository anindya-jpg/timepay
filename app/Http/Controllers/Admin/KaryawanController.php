<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KaryawanRequest;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanController extends Controller
{
    /**
     * Halaman "Data Karyawan" — list + search + filter jabatan/status.
     * Form tambah/edit dipakai lewat modal di halaman yang sama,
     * jadi tidak perlu route create()/edit() terpisah.
     */
    public function index(): Response
    {
        $karyawans = Karyawan::with('jabatan:id_jabatan,nama_jabatan')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->when(request('status'), fn ($query, $status) => $query->where('status_aktif', $status))
            ->when(request('id_jabatan'), fn ($query, $id) => $query->where('id_jabatan', $id))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/karyawan/index', [
            'karyawans' => $karyawans,
            'jabatans' => Jabatan::select('id_jabatan', 'nama_jabatan')->orderBy('nama_jabatan')->get(),
            'filters' => request()->only(['search', 'status', 'id_jabatan']),
        ]);
    }

    public function store(KaryawanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        Karyawan::create($data);

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(KaryawanRequest $request, Karyawan $karyawan): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $karyawan->update($data);

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Karyawan $karyawan): RedirectResponse
    {
        $karyawan->delete();

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}