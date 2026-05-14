<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SettingsHelpController extends Controller
{
    public function __invoke(): View
    {
        return view('settings-help.show');
    }
}
