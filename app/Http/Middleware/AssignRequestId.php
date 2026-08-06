<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Присваивает каждому запросу короткий идентификатор.
 *
 * Laravel сам добавляет содержимое Context во все записи журнала, поэтому
 * идентификатор попадает и в строку об исключении, и на страницу ошибки.
 * Благодаря этому по номеру со страницы «Внутренняя ошибка сервера» можно
 * найти саму ошибку:
 *
 *     grep A1B2C3D4 storage/logs/laravel.log
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = strtoupper(Str::random(8));

        Context::add("request_id", $requestId);

        $response = $next($request);

        // Заголовок помогает сопоставить запрос с журналом в инструментах
        // разработчика браузера, не открывая страницу ошибки.
        $response->headers->set("X-Request-Id", $requestId);

        return $response;
    }
}
