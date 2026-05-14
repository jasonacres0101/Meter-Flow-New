<?php

use App\Http\Middleware\EnsureCompanyAdmin;
use App\Http\Middleware\EnsureCustomerOperator;
use App\Http\Middleware\EnsureNotEngineer;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'platform_admin' => EnsurePlatformAdmin::class,
            'company_admin' => EnsureCompanyAdmin::class,
            'customer_operator' => EnsureCustomerOperator::class,
            'not_engineer' => EnsureNotEngineer::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'inbound/mailgun',
            'inbound/sendgrid',
            'inbound/postmark',
            'inbound/generic',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
