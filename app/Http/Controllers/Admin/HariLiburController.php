<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HariLiburController extends Controller
{
    public function index(): Response
    {
        $hariLiburs = HariLibur::when(request('tahun'), function ($query, $tahun) {
            $query->whereYear('tanggal', $tahun);
        })
            ->when(request('bulan'), function ($query, $bulan) {
                $query->whereMonth('tanggal', $bulan);
            })
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('keterangan', 'like', "%{$search}%")
                        ->orWhere('tanggal', 'like', "%{$search}%");
                });
            })
            ->orderBy('tanggal')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/hari-libur/index', [
            'hariLiburs' => $hariLiburs,
            'filters' => request()->only(['tahun', 'bulan', 'search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date', Rule::unique('hari_libur', 'tanggal')],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        HariLibur::create($data);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, HariLibur $hariLibur): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date', Rule::unique('hari_libur', 'tanggal')->ignore($hariLibur->id_libur, 'id_libur')],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $hariLibur->update($data);

        return back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}