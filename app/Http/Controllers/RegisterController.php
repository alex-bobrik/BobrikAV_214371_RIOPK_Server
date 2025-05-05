<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $companies = Company::where('type', 'insurer')->orderBy('name')->get();
        return view('auth.register', compact('companies'));
    }

    public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'company_id' => [
            'required',
            'exists:companies,id',
            function ($attribute, $value, $fail) {
                $company = Company::find($value);
                if ($company && $company->type !== 'insurer') {
                    $fail('Вы можете регистрироваться только как клиент страховой компании.');
                }
            },
        ],
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => 'client',
        'company_id' => $validated['company_id'],
    ]);

    auth()->login($user);

    return redirect()->route('client.dashboard');
}
}