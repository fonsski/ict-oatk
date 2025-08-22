<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Время хранения кэша в минутах
     *
     * @var int
     */
    protected $cacheLifetime;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->cacheLifetime = config('optimizer.defaultCacheLifetime', 60);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $lifetime  Время хранения кэша в минутах (null = использовать значение по умолчанию)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?int $lifetime = null): Response
    {
        // Если кеширование выключено или это не GET-запрос, просто передаем запрос дальше
        if (!$this->shouldCache($request)) {
            return $next($request);
        }

        // Формируем ключ кэша на основе URL и параметров запроса
        $cacheKey = $this->generateCacheKey($request);
        $cacheTime = $lifetime ?? $this->cacheLifetime;

        // Проверяем, есть ли в кэше ответ для данного запроса
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Получаем ответ
        $response = $next($request);

        // Кешируем только успешные ответы с кодом 200
        if ($this->shouldCacheResponse($response)) {
            Cache::put($cacheKey, $response, $cacheTime * 60);
        }

        return $response;
    }

    /**
     * Проверяет, нужно ли кешировать запрос
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldCache(Request $request): bool
    {
        // Кешируем только GET-запросы
        if (!$request->isMethod('GET')) {
            return false;
        }

        // Не кешируем запросы аутентифицированных пользователей
        if ($request->user()) {
            return false;
        }

        // Не кешируем запросы с определенными заголовками
        $noCacheHeaders = [
            'Cache-Control' => ['no-cache', 'no-store', 'max-age=0'],
            'Pragma' => ['no-cache'],
        ];

        foreach ($noCacheHeaders as $header => $values) {
            if ($request->headers->has($header)) {
                $headerValue = $request->headers->get($header);
                foreach ($values as $value) {
                    if (stripos($headerValue, $value) !== false) {
                        return false;
                    }
                }
            }
        }

        // Проверяем, не находится ли путь в списке исключений
        $excludedPaths = config('optimizer.exclude.routes', []);
        $path = $request->path();

        foreach ($excludedPaths as $excludedPath) {
            if (fnmatch($excludedPath, $path)) {
                return false;
            }
        }

        return config('optimizer.enableCaching', true);
    }

    /**
     * Проверяет, нужно ли кешировать ответ
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldCacheResponse(Response $response): bool
    {
        // Кешируем только успешные ответы
        if (!$response->isSuccessful()) {
            return false;
        }

        // Не кешируем ответы с заголовками no-cache
        if ($response->headers->has('Cache-Control') &&
            strpos($response->headers->get('Cache-Control'), 'no-cache') !== false) {
            return false;
        }

        // Не кешируем ответы с куками или заголовками аутентификации
        if ($response->headers->has('Set-Cookie') || $response->headers->has('Authorization')) {
            return false;
        }

        return true;
    }

    /**
     * Генерирует уникальный ключ кэша для запроса
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function generateCacheKey(Request $request): string
    {
        // Формируем ключ на основе URL и GET-параметров
        $url = $request->url();
        $queryParams = $request->query();
        ksort($queryParams); // Сортируем параметры для консистентности ключа

        $fullUrl = $url . '?' . http_build_query($queryParams);
        $key = 'response_cache:' . md5($fullUrl);

        // Учитываем язык из заголовка Accept-Language, если он есть
        if ($request->headers->has('Accept-Language')) {
            $lang = $request->header('Accept-Language');
            $key .= ':' . md5($lang);
        }

        // Учитываем мобильные устройства
        $userAgent = $request->header('User-Agent');
        $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent);

        if ($isMobile) {
            $key .= ':mobile';
        }

        return $key;
    }
}
