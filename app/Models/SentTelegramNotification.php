<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentTelegramNotification extends Model
{
    use HasFactory;

    
     * Атрибуты, которые можно массово назначать.
     *
     * @var array<int, string>

    protected $fillable = [
        'ticket_id',
        'notification_type',
        'recipients',
        'sent_at',
    ];

    
     * Атрибуты, которые должны быть приведены к нативным типам.
     *
     * @var array<string, string>

    protected $casts = [
        'recipients' => 'array',
        'sent_at' => 'datetime',
    ];

    
     * Получить заявку, связанную с уведомлением.

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    
     * Проверяет, было ли уже отправлено уведомление для данной заявки и типа.
     *
     * @param int $ticketId
     * @param string $type
     * @return bool

    public static function wasNotificationSent(int $ticketId, string $type = 'new_ticket'): bool
    {
        return static::where('ticket_id', $ticketId)
            ->where('notification_type', $type)
            ->exists();
    }

    
     * Регистрирует отправку уведомления.
     *
     * @param int $ticketId
     * @param string $type
     * @param array $recipients
     * @return static

    public static function registerSentNotification(int $ticketId, string $type = 'new_ticket', array $recipients = []): static
    {
        return static::create([
            'ticket_id' => $ticketId,
            'notification_type' => $type,
            'recipients' => $recipients,
            'sent_at' => now(),
        ]);
    }
}
