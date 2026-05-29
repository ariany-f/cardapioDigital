<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class MenuController extends Controller
{
    /** @deprecated Use a URL da filial: /{tenant}/{branch} */
    public function show(string $tenant, string $branch): RedirectResponse
    {
        return redirect()->route('tenant.branch', [
            'tenant' => $tenant,
            'branch' => $branch,
        ]);
    }
}
