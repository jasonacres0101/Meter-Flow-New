<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EngineerPinController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isEngineer(), 403);

        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,8', 'confirmed'],
        ]);

        $request->user()->setEngineerPin($data['pin']);
        session()->forget('unlocked_ticket_credentials');

        return back()->with('status', 'Engineer PIN saved.');
    }
}
