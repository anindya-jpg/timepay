<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('karyawan/auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::guard('karyawan')->attempt(
            [$field => $data['identifier'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'identifier' => 'Username atau password yang kamu masukkan salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('karyawan.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('karyawan')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('karyawan.login');
    }
}