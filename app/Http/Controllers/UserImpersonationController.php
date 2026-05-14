<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isPlatformAdmin(), 403);
        abort_if($request->session()->has('impersonator_user_id'), 422, 'You are already logged in as another user.');
        abort_if($user->isPlatformAdmin(), 422, 'Platform admin accounts cannot be impersonated.');
        abort_unless($user->is_active && (! $user->company || $user->company->is_active), 422, 'Only active users can be impersonated.');

        $request->session()->put('impersonator_user_id', $request->user()->id);
        $request->session()->put('impersonator_name', $request->user()->name);
        $request->session()->put('impersonated_user_name', $user->name);
        $request->session()->forget(['active_company_id', 'unlocked_ticket_credentials']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'You are now viewing this account as '.$user->name.'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_user_id');
        $request->session()->forget(['impersonator_name', 'impersonated_user_name', 'active_company_id', 'unlocked_ticket_credentials']);

        abort_unless($impersonatorId, 403);

        $impersonator = User::findOrFail($impersonatorId);
        abort_unless($impersonator->isPlatformAdmin() && $impersonator->is_active, 403);

        Auth::login($impersonator);
        $request->session()->regenerate();

        return redirect()->route('users.index')->with('status', 'Returned to SaaS admin account.');
    }
}
