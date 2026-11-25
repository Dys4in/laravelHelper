<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CorporatePassword;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FirstPasswordController extends Controller {
    public function edit(Request $request): View|RedirectResponse {
        $user = $request->user();
        if (! $user->needsNewPassword()) return redirect()->route('dashboard');
        return view('auth.first-password', [
            'expired' => ! $user->must_change_password,
        ]);
    }
    public function update(Request $request): RedirectResponse {
        $user = $request->user();
        if (! $user->needsNewPassword()) return redirect()->route('dashboard');
        $firstLogin = $user->must_change_password;
        $expired = ! $firstLogin;
        if ($firstLogin) $request->merge(['signature' => trim((string) $request->input('signature'))]);
        $rules = [
            'password' => ['required', 'string', 'confirmed', new CorporatePassword($user)],
        ];
        if ($expired) $rules['current_password'] = ['required', 'current_password'];
        if ($firstLogin) $rules['signature'] = [
            'required', 'string', 'max:50',
            function ($attribute, $value, $fail) use ($user) {
                if ((string) $user->signature !== (string) $value) $fail('La firma GMP no coincide con este usuario.');
            },
        ];
        $data = $request->validate($rules, [
            'signature.required' => 'La firma GMP es obligatoria.',
            'signature.max' => 'La firma GMP no puede superar los 50 caracteres.',
        ]);
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => $expired ? 'Debes elegir una contraseña diferente a la anterior.' : 'Debes elegir una contraseña diferente a la temporal.']);
        }
        $attributes = ['password' => Hash::make($data['password']), 'must_change_password' => false, 'remember_token' => Str::random(60)];
        $user->forceFill($attributes)->save();
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('status', $expired ? 'Tu contraseña ha sido actualizada.' : 'Tu contraseña personal ha sido establecida.');
    }
}
