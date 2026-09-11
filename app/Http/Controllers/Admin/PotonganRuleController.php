<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PotonganRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PotonganRuleController extends Controller
{
    public function index(): Response
    {
        $potongans = PotonganRule::when(request('search'), function ($query, $search) {
            $query->where('nama_potongan', 'like', "%{$search}%");
        })
            ->when(request('jenis'), fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->orderBy('nama_potongan')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/potongan-rule/index', [
            'potongans' => $potongans,
            'filters' => request()->only(['search', 'jenis']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_potongan' => ['required', 'string', 'max:100'],
            'jenis' => ['required', Rule::in(['per_menit', 'per_hari', 'nominal_tetap', 'persen'])],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        PotonganRule::create($data);

        return back()->with('success', 'Aturan potongan berhasil ditambahkan.');
    }

    public function update(Request $request, PotonganRule $potonganRule): RedirectResponse
    {
        $data = $request->validate([
            'nama_potongan' => ['required', 'string', 'max:100'],
            'jenis' => ['required', Rule::in(['per_menit', 'per_hari', 'nominal_tetap', 'persen'])],
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        $potonganRule->update($data);

        return back()->with('success', 'Aturan potongan berhasil diperbarui.');
    }

    public function destroy(PotonganRule $potonganRule): RedirectResponse
    {
        $potonganRule->delete();

        return back()->with('success', 'Aturan potongan berhasil dihapus.');
    }
}