<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureKaryawanAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('karyawan')->check()) {
            return redirect()->route('karyawan.login');
        }

        Inertia::share('auth.karyawan', Auth::guard('karyawan')->user());

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}