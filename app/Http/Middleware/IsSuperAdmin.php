<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            return redirect('/')->with('error', 'Akses ditolak. Hanya superadmin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
