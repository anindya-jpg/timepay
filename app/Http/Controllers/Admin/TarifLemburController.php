<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TarifLembur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TarifLemburController extends Controller
{
    /**
     * Sama seperti Pengaturan Jam Kerja, ini form tunggal (satu tarif aktif).
     */
    public function edit(): Response
    {
        $tarif = TarifLembur::latest('id_tarif')->first();

        return Inertia::render('admin/tarif-lembur/edit', [
            'tarif' => $tarif,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tarif_per_jam' => ['required', 'numeric', 'min:0'],
        ]);

        $tarif = TarifLembur::latest('id_tarif')->first();

        if ($tarif) {
            $tarif->update($data);
        } else {
            TarifLembur::create($data);
        }

        return back()->with('success', 'Tarif lembur berhasil disimpan.');
    }
}
