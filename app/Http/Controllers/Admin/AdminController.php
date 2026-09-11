<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        $admins = Admin::when(request('search'), function ($query, $search) {
            $query->where('nama', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/admin-akun/index', [
            'admins' => $admins,
            'filters' => request()->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('admin', 'username')],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $data['password'] = Hash::make($data['password']);

        Admin::create($data);

        return back()->with('success', 'Admin berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('admin', 'username')->ignore($admin->id_admin, 'id_admin')],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);

        return back()->with('success', 'Admin berhasil diperbarui.');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ($admin->id_admin === Auth::guard('admin')->id()) {
            return back()->withErrors(['admin' => 'Tidak bisa menghapus akun sendiri yang sedang login.']);
        }

        $admin->delete();

        return back()->with('success', 'Admin berhasil dihapus.');
    }
}
