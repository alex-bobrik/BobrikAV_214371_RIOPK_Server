<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Проверяем авторизацию
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        // Получаем текущего пользователя
        $user = auth()->user();

        if (!$user->is_active) {
            abort(403, 'У вас нет доступа к этой странице');
        }
        
        // Проверяем роль пользователя
        if (!in_array($user->role, $roles)) {
            abort(403, 'У вас нет доступа к этой странице');
        }
        
        return $next($request);
    }
}