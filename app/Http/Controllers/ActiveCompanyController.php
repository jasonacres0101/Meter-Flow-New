<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveCompanyController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['company_id' => ['required', 'integer']]);
        $companyIds = Tenant::accessibleCompanies($request->user())->pluck('id');

        abort_unless($companyIds->contains((int) $data['company_id']), 403);
        session(['active_company_id' => (int) $data['company_id']]);

        return back()->with('status', 'Company context changed.');
    }
}
