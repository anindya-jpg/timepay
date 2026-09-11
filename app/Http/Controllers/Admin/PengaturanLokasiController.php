<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanLokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanLokasiController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/pengaturan-lokasi/edit', [
            'lokasi' => PengaturanLokasi::first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        PengaturanLokasi::updateOrCreate(['id_lokasi' => 1], $data);

        return back()->with('success', 'Lokasi kantor berhasil disimpan.');
    }
}