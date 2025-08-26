<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;

class TestErrorController extends Controller
{
    /**
     * Тестирование страниц ошибок
     */
    public function showErrors()
    {
        return view('test.errors');
    }

    /**
     * Тест 404 - страница не найдена
     */
    public function test404()
    {
        throw new NotFoundHttpException('Тестовая страница не найдена');
    }

    /**
     * Тест 403 - доступ запрещен
     */
    public function test403()
    {
        throw new AccessDeniedHttpException('Тестовый доступ запрещен');
    }

    /**
     * Тест 401 - неавторизован
     */
    public function test401()
    {
        throw new AuthenticationException('Тестовая аутентификация не выполнена');
    }

    /**
     * Тест 500 - внутренняя ошибка сервера
     */
    public function test500()
    {
        throw new \Exception('Тестовая внутренняя ошибка сервера');
    }

    /**
     * Тест 503 - сервис недоступен
     */
    public function test503()
    {
        throw new HttpException(503, 'Тестовый сервис недоступен');
    }

    /**
     * Тест 419 - CSRF-токен истек
     */
    public function test419()
    {
        throw new HttpException(419, 'Тестовый CSRF-токен истек');
    }

    /**
     * Тест 429 - слишком много запросов
     */
    public function test429()
    {
        throw new HttpException(429, 'Тестовое превышение лимита запросов');
    }

    /**
     * Тест произвольной ошибки HTTP
     */
    public function testCustom(Request $request)
    {
        $code = $request->input('code', 418);
        throw new HttpException($code, "Тестовая ошибка с кодом $code");
    }
}
