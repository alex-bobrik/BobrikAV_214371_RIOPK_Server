<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next)
    {
        // dump(Auth::user());
        // if (!Auth::check() || Auth::user()->role != $role) {
        //     abort(403, 'Доступ запрещен');
        // }

        return $next($request);
    }
}