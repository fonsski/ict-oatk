<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\ContentSecurityPolicy::class,
        \App\Http\Middleware\XssProtection::class,
        \App\Http\Middleware\SqlInjectionProtection::class,
    ];

    protected $middlewareGroups = [
        "web" => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\BruteForceProtection::class,
            \App\Http\Middleware\SessionTimeout::class,
        ],

        "api" => [
            "throttle:api",
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        "auth" => \App\Http\Middleware\Authenticate::class,
        "auth.basic" =>
            \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        "cache.headers" => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        "can" => \Illuminate\Auth\Middleware\Authorize::class,
        "guest" => \App\Http\Middleware\RedirectIfAuthenticated::class,
        "password.confirm" =>
            \Illuminate\Auth\Middleware\RequirePassword::class,
        "signed" => \Illuminate\Routing\Middleware\ValidateSignature::class,
        "throttle" => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        "verified" => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // role middleware aliases (keep require_role for backward compatibility)
        "role" => \App\Http\Middleware\CheckRole::class,
        "require_role" => \App\Http\Middleware\CheckRole::class,
        // Security middleware aliases
        "brute.force" => \App\Http\Middleware\BruteForceProtection::class,
        "xss" => \App\Http\Middleware\XssProtection::class,
        "sql.injection" => \App\Http\Middleware\SqlInjectionProtection::class,
        "csp" => \App\Http\Middleware\ContentSecurityPolicy::class,
        "session.timeout" => \App\Http\Middleware\SessionTimeout::class,
        "cache.response" => \App\Http\Middleware\CacheResponse::class,
    ];
}
