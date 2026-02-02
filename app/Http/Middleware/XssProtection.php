<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class XssProtection
{
    
     * The patterns to search for.
     *
     * @var array

    protected $patterns = [
        
        "/<script.*?>.*?<\/script>/is",
        
        '/on\w+\s*=\s*".*?"/is',
        '/on\w+\s*=\s*\'.*?\'/is',
        
        "/javascript\s*:/is",
        
        "/expression\s*\(.*?\)/is",
        
        "/onclick|ondblclick|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onkeydown|onkeypress|onkeyup|onload|onunload|onchange|onsubmit|onreset|onselect|onblur|onfocus/is",
        
        "/data:text\/html.*?base64/is",
        
        "/<base\s+href/is",
        "/<iframe.*?>/is",
        "/<embed.*?>/is",
        "/<object.*?>/is",
        "/<form.*?>/is",
        
        "/eval\s*\(.*?\)/is",
        "/Function\s*\(.*?\)/is",
        "/document\..*?\(.*?\)/is",
        "/window\..*?\(.*?\)/is",
    ];

    
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed

    public function handle(Request $request, Closure $next): Response
    {
        
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                
                if ($this->containsXss($value)) {
                    
                    $this->logXssAttempt($value);

                    
                    $value = $this->sanitize($value);
                }
            }
        });

        
        $request->merge($input);

        
        $response = $next($request);

        
        $response->headers->set("X-XSS-Protection", "1; mode=block");

        return $response;
    }

    
     * Check if the request should skip XSS validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool

    protected function shouldSkip(Request $request): bool
    {
        
        if (
            $request->isMethod("post") &&
            ($request->hasFile("file") || $request->hasFile("image"))
        ) {
            return true;
        }

        
        $path = $request->path();
        if (Str::contains($path, ["upload-image", "images"])) {
            return true;
        }

        

        return false;
    }

    
     * Check if the value contains potential XSS attacks.
     *
     * @param  string  $value
     * @return bool

    protected function containsXss(string $value): bool
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    
     * Log the XSS attack attempt.
     *
     * @param  string  $value
     * @return void

    protected function logXssAttempt(string $value): void
    {
        \Log::warning("Potential XSS attack detected", [
            "value" => $value,
            "ip" => request()->ip(),
            "user_agent" => request()->userAgent(),
            "url" => request()->fullUrl(),
            "user_id" => auth()->id() ?? "guest",
        ]);
    }

    
     * Sanitize the value.
     *
     * @param  string  $value
     * @return string

    protected function sanitize(string $value): string
    {
        
        foreach ($this->patterns as $pattern) {
            $value = preg_replace($pattern, "", $value);
        }

        
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }
}
