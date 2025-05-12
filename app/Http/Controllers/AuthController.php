<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->put('user', Auth::user());

            $request->session()->put('auth.user', Auth::user());

            
            $request->session()->regenerate();

            return $this->authenticatedRedirect();
        }

        return back()->withErrors([
            'email' => 'Неверные учетные данные',
        ]);
    }

    protected function authenticatedRedirect()
    {
        $user = Auth::user();
        
        return match($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'underwriter' => redirect()->route('underwriter.dashboard'),
            default => redirect()->route('client.dashboard')
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}