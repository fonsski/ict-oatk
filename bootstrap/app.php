<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Регистрация обработчиков для HTTP-ошибок
        $exceptions->renderable(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
        ) {
            return response()->view("errors.404", [], 404);
        });

        $exceptions->renderable(function (
            \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e,
        ) {
            return response()->view("errors.403", [], 403);
        });

        $exceptions->renderable(function (
            \Illuminate\Auth\AuthenticationException $e,
        ) {
            return response()->view("errors.401", [], 401);
        });

        $exceptions->renderable(function (
            \Symfony\Component\HttpKernel\Exception\HttpException $e,
        ) {
            if ($e->getStatusCode() == 419) {
                return response()->view("errors.419", [], 419);
            }

            if ($e->getStatusCode() == 429) {
                return response()->view("errors.429", [], 429);
            }

            if ($e->getStatusCode() == 503) {
                return response()->view("errors.503", [], 503);
            }

            if ($e->getStatusCode() == 500) {
                return response()->view("errors.500", [], 500);
            }

            // Fallback для других HTTP-ошибок
            return response()->view(
                "errors.general",
                [
                    "code" => $e->getStatusCode(),
                    "title" => "Ошибка " . $e->getStatusCode(),
                    "message" =>
                        $e->getMessage() ?:
                        "Произошла ошибка при обработке запроса",
                    "id" => Str::random(8),
                ],
                $e->getStatusCode(),
            );
        });

        // Регистрация обработчика для всех остальных исключений
        $exceptions->renderable(function (\Throwable $e) {
            if (app()->environment("production")) {
                return response()->view(
                    "errors.500",
                    [
                        "id" => Str::random(8),
                    ],
                    500,
                );
            }
        });
    })
    ->create();
