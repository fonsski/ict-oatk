<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LogService
{
    /**
     * Логирование информационных сообщений
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        Log::channel('info')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование предупреждений
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        Log::channel('warning')->warning(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование ошибок
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        Log::channel('error')->error(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование критических ошибок
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        Log::channel('critical')->critical(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование исключений
     *
     * @param Throwable $exception Исключение
     * @param string $message Дополнительное сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function exception(Throwable $exception, string $message = '', array $context = []): void
    {
        $exceptionMessage = $message ? "$message: " . $exception->getMessage() : $exception->getMessage();
        $exceptionContext = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::formatTrace($exception),
        ];

        $context = array_merge($exceptionContext, $context);

        if ($exception instanceof \Illuminate\Database\QueryException) {
            // Маскируем SQL-запросы в продакшене
            if (app()->environment('production')) {
                $context['sql'] = '[SQL запрос скрыт в целях безопасности]';
            } else {
                $context['sql'] = $exception->getSql();
                $context['bindings'] = $exception->getBindings();
            }
        }

        Log::channel('error')->error(self::formatMessage($exceptionMessage), self::addSystemContext($context));
    }

    /**
     * Логирование доступа (аутентификация, авторизация)
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function access(string $message, array $context = []): void
    {
        Log::channel('access')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование действий с оборудованием
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function equipment(string $message, array $context = []): void
    {
        Log::channel('equipment')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование действий с заявками
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function ticket(string $message, array $context = []): void
    {
        Log::channel('ticket')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование аудита действий пользователей
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function audit(string $message, array $context = []): void
    {
        Log::channel('audit')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование запросов API
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function api(string $message, array $context = []): void
    {
        Log::channel('api')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Логирование операций с базой данных
     *
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function database(string $message, array $context = []): void
    {
        Log::channel('database')->info(self::formatMessage($message), self::addSystemContext($context));
    }

    /**
     * Добавление системной информации к контексту
     *
     * @param array $context Исходный контекст
     * @return array Дополненный контекст
     */
    private static function addSystemContext(array $context): array
    {
        $request = request();
        $user = auth()->user();

        $systemContext = [
            'timestamp' => now()->toDateTimeString(),
            'request_id' => Str::uuid()->toString(),
        ];

        if ($request) {
            $systemContext['ip'] = $request->ip();
            $systemContext['url'] = $request->fullUrl();
            $systemContext['method'] = $request->method();
            $systemContext['user_agent'] = $request->userAgent();
        }

        if ($user) {
            $systemContext['user_id'] = $user->id;
            $systemContext['user_name'] = $user->name;
            $systemContext['user_email'] = $user->email;
            $systemContext['user_role'] = optional($user->role)->name;
        }

        return array_merge($systemContext, $context);
    }

    /**
     * Форматирование сообщения для лога
     *
     * @param string $message Исходное сообщение
     * @return string Отформатированное сообщение
     */
    private static function formatMessage(string $message): string
    {
        return '[' . app()->environment() . '] ' . $message;
    }

    /**
     * Форматирование стека вызовов для более читаемого отображения
     *
     * @param Throwable $exception Исключение
     * @param int $limit Максимальное количество строк трейса
     * @return array Отформатированный трейс
     */
    private static function formatTrace(Throwable $exception, int $limit = 10): array
    {
        $trace = $exception->getTrace();
        $formattedTrace = [];

        $count = 0;
        foreach ($trace as $entry) {
            if ($count >= $limit) break;

            $class = $entry['class'] ?? '';
            $type = $entry['type'] ?? '';
            $function = $entry['function'] ?? '';
            $file = $entry['file'] ?? 'unknown';
            $line = $entry['line'] ?? 0;

            $formattedTrace[] = "#$count $file($line): $class$type$function()";
            $count++;
        }

        if (count($trace) > $limit) {
            $formattedTrace[] = "... " . (count($trace) - $limit) . " more";
        }

        return $formattedTrace;
    }
}
