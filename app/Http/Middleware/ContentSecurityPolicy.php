<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $cspDirectives = [
            "default-src" => "'self'",
            "script-src" =>
                "'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net", // Only trusted CDNs
            "style-src" =>
                "'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "img-src" => "'self' data: https:", // Allow inline images and secure external images
            "font-src" =>
                "'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src" => "'self' https://api.example.com", // Add your API domains here
            "frame-src" => "'self'", // Allow iframes from the same origin
            "object-src" => "'none'", // Block <object>, <embed>, and <applet> elements
            "base-uri" => "'self'", // Restricts use of <base> tag
            "form-action" => "'self'", // Forms can only submit to same origin
            "frame-ancestors" => "'none'", // Prevent clickjacking
            "upgrade-insecure-requests" => "", // Force HTTPS
            "block-all-mixed-content" => "", // Block mixed content
            "require-trusted-types-for" => "'script'", // Helps prevent DOM XSS
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

        // Set additional security headers
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

        // Add Strict-Transport-Security header for HTTPS
        if (!app()->environment("local")) {
            $response->headers->set(
                "Strict-Transport-Security",
                "max-age=31536000; includeSubDomains; preload",
            );
        }

        // Clear potentially sensitive headers
        $response->headers->remove("Server");
        $response->headers->remove("X-Powered-By");

        return $response;
    }
}
