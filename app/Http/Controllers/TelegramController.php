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

    
     * Обработчик webhook от Telegram

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

            
            if (!isset($update['message'])) {
                return response()->json(['status' => 'ok']);
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            
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

    
     * Обрабатывает входящее сообщение

    protected function processMessage(int $chatId, string $text, array $message): void
    {
        Log::info('Processing message', [
            'chat_id' => $chatId,
            'text' => $text,
            'is_command' => strpos($text, '/') === 0,
            'in_auth_process' => $this->authService->isUserInAuthProcess($chatId)
        ]);

        
        if ($this->authService->isUserInAuthProcess($chatId)) {
            Log::info('User in auth process, processing auth message', ['chat_id' => $chatId]);
            $this->processAuthMessage($chatId, $text);
            return;
        }

        
        if (strpos($text, '/') === 0) {
            Log::info('Processing command', ['chat_id' => $chatId, 'command' => $text]);
            $this->processCommand($chatId, $text);
            return;
        }

        
        Log::info('Processing unknown message', ['chat_id' => $chatId, 'text' => $text]);
        $this->handleUnknownMessage($chatId, $text);
    }

    
     * Обрабатывает сообщения в процессе авторизации

    protected function processAuthMessage(int $chatId, string $text): void
    {
        $authState = $this->authService->getAuthState($chatId);
        
        Log::info('Processing auth message', [
            'chat_id' => $chatId,
            'text' => $text,
            'auth_state' => $authState
        ]);
        
        if (!$authState) {
            Log::warning('No auth state found', ['chat_id' => $chatId]);
            return;
        }

        switch ($authState['step']) {
            case 'phone':
                Log::info('Processing phone step', ['chat_id' => $chatId]);
                $this->authService->processPhone($chatId, $text);
                break;
            case 'password':
                Log::info('Processing password step', ['chat_id' => $chatId]);
                $this->authService->processPassword($chatId, $text);
                break;
        }
    }

    
     * Обрабатывает команды

    protected function processCommand(int $chatId, string $text): void
    {
        $command = strtolower(trim($text));

        
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

        if (preg_match('/^\/close_(\d+)$/', $command, $matches)) {
            $ticketId = (int) $matches[1];
            $this->commandService->handleCloseTicket($chatId, $ticketId);
            return;
        }

        
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
            case '/all_tickets':
                $this->commandService->handleAllTickets($chatId);
                break;
            case '/active':
                $this->commandService->handleActive($chatId);
                break;
            case '/stats':
                $this->commandService->handleStats($chatId);
                break;
            case '/rooms':
                $this->commandService->handleRooms($chatId);
                break;
            case '/equipment':
                $this->commandService->handleEquipment($chatId);
                break;
            case '/users':
                $this->commandService->handleUsers($chatId);
                break;
            default:
                $this->handleUnknownCommand($chatId, $command);
                break;
        }
    }

    
     * Обрабатывает неизвестные команды

    protected function handleUnknownCommand(int $chatId, string $command): void
    {
        $message = "❓ <b>Неизвестная команда</b>\n\n";
        $message .= "Команда <code>{$command}</code> не распознана.\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Обрабатывает неизвестные сообщения

    protected function handleUnknownMessage(int $chatId, string $text): void
    {
        $message = "🤔 <b>Не понимаю</b>\n\n";
        $message .= "Я понимаю только команды, начинающиеся с <code>/</code>.\n\n";
        $message .= "Отправьте <code>/help</code> для получения списка доступных команд.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    
     * Тестовый метод для проверки работы контроллера

    public function test()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Telegram controller is working',
            'timestamp' => now()->toISOString()
        ]);
    }
}
