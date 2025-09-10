<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use App\Services\TelegramAuthService;
use App\Services\TelegramCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected TelegramService $telegramService;
    protected TelegramAuthService $authService;
    protected TelegramCommandService $commandService;

    public function __construct(
        TelegramService $telegramService,
        TelegramAuthService $authService,
        TelegramCommandService $commandService
    ) {
        $this->telegramService = $telegramService;
        $this->authService = $authService;
        $this->commandService = $commandService;
    }

    /**
     * Обработчик webhook от Telegram
     */
    public function webhook(Request $request)
    {
        try {
            $update = $request->all();
            
            Log::info('Received Telegram webhook', [
                'update_id' => $update['update_id'] ?? null,
                'message_id' => $update['message']['message_id'] ?? null,
                'chat_id' => $update['message']['chat']['id'] ?? null,
                'text' => $update['message']['text'] ?? null
            ]);

            // Обрабатываем только сообщения
            if (!isset($update['message'])) {
                return response()->json(['status' => 'ok']);
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            // Обрабатываем сообщение
            $this->processMessage($chatId, $text, $message);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Error processing Telegram webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Обрабатывает входящее сообщение
     */
    protected function processMessage(int $chatId, string $text, array $message): void
    {
        // Проверяем, находится ли пользователь в процессе авторизации
        if ($this->authService->isUserInAuthProcess($chatId)) {
            $this->processAuthMessage($chatId, $text);
            return;
        }

        // Обрабатываем команды
        if (strpos($text, '/') === 0) {
            $this->processCommand($chatId, $text);
            return;
        }

        // Обычные сообщения
        $this->handleUnknownMessage($chatId, $text);
    }

    /**
     * Обрабатывает сообщения в процессе авторизации
     */
    protected function processAuthMessage(int $chatId, string $text): void
    {
        $authState = $this->authService->getAuthState($chatId);
        
        if (!$authState) {
            return;
        }

        switch ($authState['step']) {
            case 'phone':
                $this->authService->processPhone($chatId, $text);
                break;
            case 'password':
                $this->authService->processPassword($chatId, $text);
                break;
        }
    }

    /**
     * Обрабатывает команды
     */
    protected function processCommand(int $chatId, string $text): void
    {
        $command = strtolower(trim($text));

        // Обрабатываем команды с параметрами
        if (preg_match('/^\/ticket_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $this->commandService->handleTicketDetails($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/start_ticket_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $this->commandService->handleStartTicket($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/assign_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $this->commandService->handleAssignTicket($chatId, $ticketId);
            return;
        }

        if (preg_match('/^\/resolve_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $this->commandService->handleResolveTicket($chatId, $ticketId);
            return;
        }

        // Обрабатываем простые команды
        switch ($command) {
            case '/start':
                $this->commandService->handleStart($chatId);
                break;
            case '/help':
                $this->commandService->handleHelp($chatId);
                break;
            case '/login':
                $this->authService->startAuth($chatId);
                break;
            case '/logout':
                $this->authService->logout($chatId);
                break;
            case '/tickets':
                $this->commandService->handleTickets($chatId);
                break;
            case '/active':
                $this->commandService->handleActive($chatId);
                break;
            default:
                $this->handleUnknownCommand($chatId, $command);
                break;
        }
    }

    /**
     * Обрабатывает неизвестные команды
     */
    protected function handleUnknownCommand(int $chatId, string $command): void
    {
        $message = "❓ <b>Неизвестная команда</b>\n\n";
        $message .= "Команда <code>{$command}</code> не распознана.\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Обрабатывает неизвестные сообщения
     */
    protected function handleUnknownMessage(int $chatId, string $text): void
    {
        $message = "🤔 <b>Не понимаю</b>\n\n";
        $message .= "Я понимаю только команды, начинающиеся с <code>/</code>.\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Тестовый метод для проверки работы контроллера
     */
    public function test()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Telegram controller is working',
            'timestamp' => now()->toISOString()
        ]);
    }
}
