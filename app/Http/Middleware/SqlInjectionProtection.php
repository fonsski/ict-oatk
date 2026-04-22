<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SqlInjectionProtection
{
    /**
     * Common SQL injection patterns to check for
     *
     * @var array
     */
    protected $patterns = [
        "/\bUNION\b/i",
        "/\bSELECT\b\s+.*?\bFROM\b/i",
        "/\bINSERT\b\s+\bINTO\b/i",
        "/\bUPDATE\b\s+.*?\bSET\b/i",
        "/\bDELETE\b\s+\bFROM\b/i",
        "/\bDROP\b\s+\bTABLE\b/i",
        "/\bALTER\b\s+\bTABLE\b/i",
        "/\bEXEC\b\s*\(/i",
        "/--/",
        '/;\s*$/',
        "/\/\*.*?\*\//", // SQL comments
        "/SLEEP\(\s*\d+\s*\)/i",
        "/BENCHMARK\(\s*\d+\s*,/i",
        "/WAITFOR\s+DELAY\s+/i",
        "/INFORMATION_SCHEMA/i",
        "/sysobjects/i",
        "/xp_cmdshell/i",
        "/load_file/i",
        "/outfile/i",
        "/dumpfile/i",
        "/hex\(/i",
        "/char\(/i",
        "/concat\(/i",
        "/cast\(/i",
        "/convert\(/i",
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
        // Пропускаем проверку для некоторых путей, например API документации или статических ресурсов
        $path = $request->path();
        $excludedPaths = [
            "api/docs",
            "public",
            "assets",
            "images",
            "css",
            "js",
            "knowledge/upload-image",
            "homepage-faq/upload-image",
            "api/notifications", // Исключаем API уведомлений
        ];
        foreach ($excludedPaths as $excludedPath) {
            if (Str::startsWith($path, $excludedPath)) {
                return $next($request);
            }
        }

        // Check GET parameters
        foreach ($request->query() as $key => $value) {
            $this->checkForSqlInjection($key, $value, $request);
        }

        // Check POST parameters (skip for file uploads)
        if (!$request->hasFile("image") && !$request->hasFile("file")) {
            foreach ($request->post() as $key => $value) {
                $this->checkForSqlInjection($key, $value, $request);
            }
        }

        // Check JSON parameters if content type is application/json
        if ($request->isJson()) {
            $data = $request->json()->all();
            $this->recursiveCheck($data, $request);
        }

        return $next($request);
    }

    /**
     * Recursively check arrays for SQL injection patterns
     *
     * @param array $data
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function recursiveCheck(array $data, Request $request): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->recursiveCheck($value, $request);
            } else {
                $this->checkForSqlInjection($key, $value, $request);
            }
        }
    }

    /**
     * Check if a given string contains SQL injection patterns
     *
     * @param string $key
     * @param mixed $value
     * @param \Illuminate\Http\Request $request
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function checkForSqlInjection(
        $key,
        $value,
        Request $request,
    ): void {
        if (!is_string($value)) {
            return;
        }

        // Skip if this is a JSON, file upload field, or image upload
        if (
            Str::startsWith($key, "_token") ||
            Str::startsWith($key, "_method") ||
            $key === "image" ||
            $key === "file" ||
            $key === "content" || // Пропускаем поле content для статей базы знаний
            $key === "description" // Пропускаем поле description
        ) {
            return;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $ip = $request->ip();
                $userAgent = $request->userAgent();
                $path = $request->fullUrl();

                // Log the attempt
                \Log::warning("SQL Injection attempt detected", [
                    "ip" => $ip,
                    "user_agent" => $userAgent,
                    "path" => $path,
                    "key" => $key,
                    "value" => $value,
                    "pattern" => $pattern,
                    "user_id" => Auth::id() ?? "guest",
                ]);

                // Увеличиваем счетчик подозрительных запросов для данного IP
                $cacheKey = "sql_injection_attempts:" . $ip;
                $attempts = \Cache::get($cacheKey, 0) + 1;
                \Cache::put($cacheKey, $attempts, 60 * 24); // хранить 24 часа

                // Если слишком много попыток, добавляем IP в черный список
                if ($attempts >= 5) {
                    \Cache::put("blacklisted_ip:" . $ip, true, 60 * 24 * 7); // блокируем на неделю
                    \Log::alert(
                        "IP добавлен в черный список из-за множественных попыток SQL инъекции",
                        [
                            "ip" => $ip,
                            "attempts" => $attempts,
                        ],
                    );
                }

                // Блокируем подозрительные запросы
                if (Auth::check()) {
                    // Для авторизованных пользователей логируем и блокируем
                    abort(
                        403,
                        "Подозрительный запрос заблокирован системой безопасности.",
                    );
                } else {
                    // Для неавторизованных пользователей перенаправляем на главную
                    abort(403, "Доступ запрещен.");
                }
            }
        }
    }
}
