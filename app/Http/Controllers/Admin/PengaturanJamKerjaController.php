<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanJamKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanJamKerjaController extends Controller
{
    /**
     * Cuma ada 1 baris pengaturan aktif (sesuai wireframe), jadi
     * halaman ini berupa form tunggal, bukan tabel list.
     */
    public function edit(): Response
    {
        $pengaturan = PengaturanJamKerja::first();

        return Inertia::render('admin/pengaturanjamkerja/edit', [
            'pengaturan' => $pengaturan,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jam_masuk_standar' => ['required', 'date_format:H:i'],
            'jam_pulang_standar' => ['required', 'date_format:H:i', 'after:jam_masuk_standar'],
        ]);

        $pengaturan = PengaturanJamKerja::first();

        if ($pengaturan) {
            $pengaturan->update($data);
        } else {
            PengaturanJamKerja::create($data);
        }

        return back()->with('success', 'Pengaturan jam kerja berhasil disimpan.');
    }
}
