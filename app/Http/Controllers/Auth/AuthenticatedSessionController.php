<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        if (! $request->user()->is_active || ($request->user()->company && ! $request->user()->company->is_active)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'This account is inactive.']);
        }

        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($request->user()->isPlatformAdmin() ? route('companies.index') : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
