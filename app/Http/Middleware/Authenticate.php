<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if ($this->auth->guard('web')->guest()) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }

    protected function redirectTo($request)
    {
        // Для API запросов
        if ($request->is('api/*')) {
            return null;
        }
        
        // Для веб-запросов
        return route('login');
    }
}
