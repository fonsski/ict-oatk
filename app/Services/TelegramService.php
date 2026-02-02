<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TelegramService
{
    protected string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.telegram.token');
        $this->apiUrl = "https:
        
        if (empty($this->token)) {
            throw new \Exception('Telegram bot token is not configured');
        }
    }

    
     * Отправляет сообщение в чат

    public function sendMessage(int $chatId, string $text, array $options = []): bool
    {
        
        $messageHash = md5($chatId . $text);
        $cacheKey = "telegram_message_{$messageHash}";
        
        if (Cache::has($cacheKey)) {
            Log::warning('Duplicate message prevented', [
                'chat_id' => $chatId,
                'text_preview' => substr($text, 0, 100)
            ]);
            return false;
        }
        
        Cache::put($cacheKey, true, 30); 

        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options);

        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", $params);
            $result = $response->json();

            if (!$response->successful() || !($result['ok'] ?? false)) {
                Log::error('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'error' => $result['description'] ?? 'Unknown error',
                    'response' => $result
                ]);
                return false;
            }

            Log::info('Telegram message sent successfully', [
                'chat_id' => $chatId,
                'message_id' => $result['result']['message_id'] ?? null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception when sending Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    
     * Получает информацию о боте

    public function getBotInfo(): ?array
    {
        try {
            $response = Http::get("{$this->apiUrl}/getMe");
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                return $result['result'];
            }

            Log::error('Failed to get bot info', ['response' => $result]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception when getting bot info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    
     * Устанавливает webhook

    public function setWebhook(string $url): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/setWebhook", [
                'url' => $url
            ]);
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                Log::info('Webhook set successfully', ['url' => $url]);
                return true;
            }

            Log::error('Failed to set webhook', ['url' => $url, 'response' => $result]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception when setting webhook', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    
     * Удаляет webhook

    public function deleteWebhook(): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/deleteWebhook");
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                Log::info('Webhook deleted successfully');
                return true;
            }

            Log::error('Failed to delete webhook', ['response' => $result]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception when deleting webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    
     * Получает информацию о webhook

    public function getWebhookInfo(): ?array
    {
        try {
            $response = Http::get("{$this->apiUrl}/getWebhookInfo");
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                return $result['result'];
            }

            Log::error('Failed to get webhook info', ['response' => $result]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception when getting webhook info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    
     * Получает обновления (для long polling)

    public function getUpdates(int $offset = 0, int $timeout = 30): array
    {
        try {
            $response = Http::get("{$this->apiUrl}/getUpdates", [
                'offset' => $offset,
                'timeout' => $timeout,
                'limit' => 100
            ]);
            $result = $response->json();

            if ($response->successful() && ($result['ok'] ?? false)) {
                return $result['result'] ?? [];
            }

            Log::error('Failed to get updates', ['response' => $result]);
            return [];
        } catch (\Exception $e) {
            Log::error('Exception when getting updates', ['error' => $e->getMessage()]);
            return [];
        }
    }

    
     * Отправляет уведомление о новой заявке

    public function sendNewTicketNotification(int $chatId, array $ticketData): bool
    {
        $message = "🆕 <b>Новая заявка 
        $message .= "📋 <b>Название:</b> {$ticketData['title']}\n";
        $message .= "📂 <b>Категория:</b> " . $this->getCategoryEmoji($ticketData['category']) . " " . $this->getHumanReadableCategory($ticketData['category']) . "\n";
        $message .= "⚡ <b>Приоритет:</b> " . $this->getPriorityEmoji($ticketData['priority']) . " " . $this->getHumanReadablePriority($ticketData['priority']) . "\n";
        $message .= "👤 <b>Заявитель:</b> {$ticketData['reporter_name']}\n\n";
        $message .= "🔍 <code>/ticket_{$ticketData['id']}</code> - Подробнее\n";
        $message .= "▶️ <code>/start_ticket_{$ticketData['id']}</code> - Взять в работу";

        return $this->sendMessage($chatId, $message);
    }

    
     * Отправляет уведомление об изменении статуса заявки

    public function sendTicketStatusNotification(int $chatId, array $ticketData, string $oldStatus, string $newStatus): bool
    {
        $message = "🔄 <b>Заявка 
        $message .= "📋 <b>Название:</b> {$ticketData['title']}\n";
        $message .= "📊 <b>Статус изменен:</b> " . $this->getStatusEmoji($oldStatus) . " → " . $this->getStatusEmoji($newStatus) . "\n";
        $message .= "👤 <b>Исполнитель:</b> {$ticketData['assigned_to_name']}\n\n";
        $message .= "🔍 <code>/ticket_{$ticketData['id']}</code> - Подробнее";

        return $this->sendMessage($chatId, $message);
    }

    
     * Получает эмодзи для статуса

    protected function getStatusEmoji(string $status): string
    {
        return match ($status) {
            'open' => '🆕',
            'in_progress' => '🔄',
            'resolved' => '✅',
            'closed' => '🔒',
            default => '❓'
        };
    }

    
     * Получает эмодзи для приоритета

    protected function getPriorityEmoji(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => '🟢',
            'medium' => '🟡',
            'high' => '🟠',
            'urgent' => '🔴',
            default => '❓'
        };
    }

    
     * Получает человекочитаемый приоритет

    protected function getHumanReadablePriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий',
            'urgent' => 'Срочный',
            default => ucfirst($priority)
        };
    }

    
     * Получает эмодзи для категории

    protected function getCategoryEmoji(string $category): string
    {
        return match (strtolower($category)) {
            'hardware' => '💻',
            'software' => '💿',
            'network' => '🌐',
            'account' => '👤',
            'other' => '📋',
            default => '❓'
        };
    }

    
     * Получает человекочитаемую категорию

    protected function getHumanReadableCategory(string $category): string
    {
        return match (strtolower($category)) {
            'hardware' => 'Оборудование',
            'software' => 'Программное обеспечение',
            'network' => 'Сеть и интернет',
            'account' => 'Учетная запись',
            'other' => 'Другое',
            default => $category
        };
    }
}
