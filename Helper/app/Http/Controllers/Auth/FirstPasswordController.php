<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CorporatePassword;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FirstPasswordController extends Controller {
    public function edit(Request $request): View|RedirectResponse {
        if (! $request->user()->must_change_password) return redirect()->route('dashboard');
        return view('auth.first-password');
    }
    public function update(Request $request): RedirectResponse {
        $user = $request->user();
        if (! $user->must_change_password) return redirect()->route('dashboard');
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', new CorporatePassword($user)],
        ]);
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Debes elegir una contraseña diferente a la temporal.']);
        }
        $user->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false, 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('status', 'Tu contraseña personal ha sido establecida.');
    }
}
