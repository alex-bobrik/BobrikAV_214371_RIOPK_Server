<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    /**
     * Display user settings form.
     */
    public function index()
    {
        $user = Auth::user();
        return view('client.settings.index', compact('user'));
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        // $user->update($validated);

        return redirect()->route('client.settings')
            ->with('success', 'Профиль успешно обновлен');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Auth::user()->update([
        //     'password' => Hash::make($request->password),
        // ]);

        return redirect()->route('client.settings')
            ->with('success', 'Пароль успешно изменен');
    }
}