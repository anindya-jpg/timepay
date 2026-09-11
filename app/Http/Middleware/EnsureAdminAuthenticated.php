<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Memastikan admin sudah login menggunakan guard 'admin'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kalau belum login sebagai admin,
        // jangan izinkan mengakses halaman admin.
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // Share data admin yang sedang login ke Inertia.
        Inertia::share('auth.admin', Auth::guard('admin')->user());

        $response = $next($request);

        // Halaman admin tidak boleh disimpan browser.
        return $this->disableCache($response);
    }

    /**
     * Mencegah browser menyimpan halaman admin
     * ke cache/history setelah logout.
     */
    protected function disableCache(Response $response): Response
    {
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );

        $response->headers->set(
            'Pragma',
            'no-cache'
        );

        $response->headers->set(
            'Expires',
            '0'
        );

        return $response;
    }
}
