<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends Controller
{
    /**
     * Menampilkan halaman profil karyawan.
     */
    public function edit(): Response
    {
        $karyawan = Auth::guard('karyawan')->user();

        $karyawan->load('jabatan');

        return Inertia::render('karyawan/profil', [
            'karyawan' => [
                'id_karyawan' => $karyawan->id_karyawan,
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'jenis_kelamin' => $karyawan->jenis_kelamin,
                'alamat' => $karyawan->alamat,
                'no_hp' => $karyawan->no_hp,
                'email' => $karyawan->email,
                'no_rekening' => $karyawan->no_rekening,
                'username' => $karyawan->username,
                'foto' => $karyawan->foto,
                'tanggal_masuk' => $karyawan->tanggal_masuk?->format('d/m/Y'),
                'status_aktif' => $karyawan->status_aktif,
                'jabatan' => $karyawan->jabatan?->nama_jabatan,
            ],
        ]);
    }

    /**
     * Menyimpan perubahan profil.
     */
    public function update(Request $request): RedirectResponse
    {
        $karyawan = Auth::guard('karyawan')->user();

        $validated = $request->validate([
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('karyawan', 'email')
                    ->ignore($karyawan->id_karyawan, 'id_karyawan'),
            ],

            'no_hp' => [
                'nullable',
                'string',
                'max:20',
            ],

            'alamat' => [
                'nullable',
                'string',
                'max:255',
            ],

            'no_rekening' => [
                'nullable',
                'string',
                'max:50',
            ],

            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('karyawan', 'username')
                    ->ignore($karyawan->id_karyawan, 'id_karyawan'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Data yang boleh diubah
        |--------------------------------------------------------------------------
        */

        $karyawan->email = $validated['email'];
        $karyawan->no_hp = $validated['no_hp'] ?? null;
        $karyawan->alamat = $validated['alamat'] ?? null;
        $karyawan->no_rekening = $validated['no_rekening'] ?? null;
        $karyawan->username = $validated['username'];

        /*
        |--------------------------------------------------------------------------
        | Password
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['password'])) {
            $karyawan->password = $validated['password'];
        }

        /*
        |--------------------------------------------------------------------------
        | Foto Baru
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');

            if ($foto->isValid()) {

                // Simpan foto baru
                $fotoBaru = $foto->store('profil/karyawan', 'public');

                // Hapus foto lama
                if (
                    !empty($karyawan->foto) &&
                    Storage::disk('public')->exists($karyawan->foto)
                ) {
                    Storage::disk('public')->delete($karyawan->foto);
                }

                // Simpan path foto baru ke database
                $karyawan->foto = $fotoBaru;
            }
        }

        $karyawan->save();

        return redirect()
            ->route('karyawan.profil.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Menghapus foto profil.
     */
    public function destroyFoto(): RedirectResponse
    {
        $karyawan = Auth::guard('karyawan')->user();

        if (
            !empty($karyawan->foto) &&
            Storage::disk('public')->exists($karyawan->foto)
        ) {
            Storage::disk('public')->delete($karyawan->foto);
        }

        $karyawan->foto = null;
        $karyawan->save();

        return redirect()
            ->route('karyawan.profil.edit')
            ->with('success', 'Foto profil berhasil dihapus.');
    }
}

