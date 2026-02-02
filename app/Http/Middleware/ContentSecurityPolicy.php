<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $cspDirectives = [
            "default-src" => "'self'",
            "script-src" =>
                "'self' https:
            "style-src" =>
                "'self' https:
            "img-src" => "'self' data: https:", 
            "font-src" =>
                "'self' https:
            "connect-src" => "'self' https:
            "frame-src" => "'self'", 
            "object-src" => "'none'", 
            "base-uri" => "'self'", 
            "form-action" => "'self'", 
            "frame-ancestors" => "'none'", 
            "upgrade-insecure-requests" => "", 
            "block-all-mixed-content" => "", 
            "require-trusted-types-for" => "'script'", 
        ];

        $cspHeader = "";
        foreach ($cspDirectives as $directive => $value) {
            if (!empty($value)) {
                $cspHeader .= "$directive $value; ";
            } else {
                $cspHeader .= "$directive; ";
            }
        }

        $response->headers->set("Content-Security-Policy", trim($cspHeader));

        
        $response->headers->set("X-Content-Type-Options", "nosniff");
        $response->headers->set("X-XSS-Protection", "1; mode=block");
        $response->headers->set("X-Frame-Options", "DENY");
        $response->headers->set(
            "Referrer-Policy",
            "strict-origin-when-cross-origin",
        );
        $response->headers->set(
            "Permissions-Policy",
            "camera=(), microphone=(), geolocation=(), interest-cohort=(), payment=(), usb=(), screen-wake-lock=(), ambient-light-sensor=()",
        );

        
        if (!app()->environment("local")) {
            $response->headers->set(
                "Strict-Transport-Security",
                "max-age=31536000; includeSubDomains; preload",
            );
        }

        
        $response->headers->remove("Server");
        $response->headers->remove("X-Powered-By");

        return $response;
    }
}
