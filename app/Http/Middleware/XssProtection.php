<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XssProtection
{
    /**
     * The patterns to search for.
     *
     * @var array
     */
    protected $patterns = [
        // Script tags
        '/<script.*?>.*?<\/script>/is',
        // Script attributes
        '/on\w+\s*=\s*".*?"/is',
        '/on\w+\s*=\s*\'.*?\'/is',
        // JavaScript URLs
        '/javascript\s*:/is',
        // CSS expression
        '/expression\s*\(.*?\)/is',
        // Inline event handlers
        '/onclick|ondblclick|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onkeydown|onkeypress|onkeyup|onload|onunload|onchange|onsubmit|onreset|onselect|onblur|onfocus/is',
        // Data URLs
        '/data:text\/html.*?base64/is',
        // Common attack vectors
        '/<base\s+href/is',
        '/<iframe.*?>/is',
        '/<embed.*?>/is',
        '/<object.*?>/is',
        '/<form.*?>/is',
        // JavaScript eval() and Function()
        '/eval\s*\(.*?\)/is',
        '/Function\s*\(.*?\)/is',
        '/document\..*?\(.*?\)/is',
        '/window\..*?\(.*?\)/is',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip XSS validation for certain routes if needed
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Sanitize input data
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                // Detect potential XSS attacks
                if ($this->containsXss($value)) {
                    // Log the attack attempt
                    $this->logXssAttempt($value);

                    // Strip or encode the dangerous content
                    $value = $this->sanitize($value);
                }
            }
        });

        // Replace request input with sanitized data
        $request->merge($input);

        // Get the response
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }

    /**
     * Check if the request should skip XSS validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldSkip(Request $request): bool
    {
        // Skip XSS validation for file uploads
        if ($request->isMethod('post') && $request->hasFile('file')) {
            return true;
        }

        // You can add more conditions here as needed

        return false;
    }

    /**
     * Check if the value contains potential XSS attacks.
     *
     * @param  string  $value
     * @return bool
     */
    protected function containsXss(string $value): bool
    {
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the XSS attack attempt.
     *
     * @param  string  $value
     * @return void
     */
    protected function logXssAttempt(string $value): void
    {
        \Log::warning('Potential XSS attack detected', [
            'value' => $value,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id() ?? 'guest',
        ]);
    }

    /**
     * Sanitize the value.
     *
     * @param  string  $value
     * @return string
     */
    protected function sanitize(string $value): string
    {
        // First, remove all known dangerous patterns
        foreach ($this->patterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        // Then encode special characters
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
