<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotEngineer
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isEngineer(), 403);

        return $next($request);
    }
}
