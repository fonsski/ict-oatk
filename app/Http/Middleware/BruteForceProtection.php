<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BruteForceProtection
{
    
     * Maximum number of failed attempts before temporarily blocking the IP.
     *
     * @var int

    protected $maxAttempts = 5;

    
     * Number of minutes to lock the user out.
     *
     * @var int

    protected $decayMinutes = 15;

    
     * Routes that should be protected by this middleware.
     *
     * @var array

    protected $protectedRoutes = [
        "login",
        "password/reset",
        "register",
        "user.reset-password",
    ];

    
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed

    public function handle(Request $request, Closure $next): Response
    {
        
        if ($this->shouldProtectRoute($request)) {
            $key = $this->resolveRequestSignature($request);

            
            if ($this->ipIsBanned($request)) {
                Log::warning("Blocked attempt from banned IP", [
                    "ip" => $request->ip(),
                    "user_agent" => $request->userAgent(),
                    "url" => $request->fullUrl(),
                ]);

                return $this->buildResponse($request);
            }

            
            if ($request->isMethod("post")) {
                $executed = RateLimiter::attempt(
                    $key,
                    $this->maxAttempts,
                    function () {
                        
                    },
                    $this->decayMinutes * 60,
                );

                if (!$executed) {
                    $this->banIp($request);
                    return $this->buildResponse($request);
                }

                
                if ($this->wasSuccessful($request)) {
                    RateLimiter::clear($key);
                }
            }
        }

        return $next($request);
    }

    
     * Determine if the request should be protected by this middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool

    protected function shouldProtectRoute(Request $request): bool
    {
        $routeName = $request->route() ? $request->route()->getName() : "";

        foreach ($this->protectedRoutes as $route) {
            if (
                Str::startsWith($routeName, $route) ||
                Str::contains($request->path(), $route)
            ) {
                return true;
            }
        }

        return false;
    }

    
     * Resolve the request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string

    protected function resolveRequestSignature(Request $request): string
    {
        
        return sha1($request->ip() . "|" . $request->route()->getName());
    }

    
     * Check if an IP is banned.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool

    protected function ipIsBanned(Request $request): bool
    {
        return Cache::has("ban_ip_" . $request->ip());
    }

    
     * Ban an IP temporarily.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void

    protected function banIp(Request $request): void
    {
        $ip = $request->ip();
        $banDuration = $this->decayMinutes * 2; 

        Cache::put("ban_ip_" . $ip, true, $banDuration * 60);

        Log::warning("IP temporarily banned due to too many login attempts", [
            "ip" => $ip,
            "user_agent" => $request->userAgent(),
            "ban_duration_minutes" => $banDuration,
        ]);
    }

    
     * Determine if the authentication attempt was successful.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool

    protected function wasSuccessful(Request $request): bool
    {
        
        if (Str::contains($request->route()->getName(), "login")) {
            return auth()->check();
        }

        
        if (Str::contains($request->route()->getName(), "password")) {
            return session()->has("status") && !session()->has("errors");
        }

        
        if (Str::contains($request->route()->getName(), "register")) {
            return session()->has("success") && !session()->has("errors");
        }

        
        return false;
    }

    
     * Build the response for rate limitation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response

    protected function buildResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(
                [
                    "message" =>
                        "Too many login attempts. Please try again later.",
                ],
                429,
            );
        }

        return redirect()
            ->back()
            ->withInput($request->except("password"))
            ->withErrors([
                "phone" => "Too many login attempts. Please try again later.",
            ]);
    }
}
