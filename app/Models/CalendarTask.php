<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Личная задача сотрудника с необязательным сроком.
 */
class CalendarTask extends Model
{
    use HasDocuments, HasFactory;

    public const PRIORITY_LOW = "low";
    public const PRIORITY_MEDIUM = "medium";
    public const PRIORITY_HIGH = "high";

    public const PRIORITIES = [
        self::PRIORITY_LOW => "Низкий",
        self::PRIORITY_MEDIUM => "Средний",
        self::PRIORITY_HIGH => "Высокий",
    ];

    protected $fillable = [
        "title",
        "description",
        "user_id",
        "created_by_user_id",
        "due_at",
        "due_all_day",
        "completed_at",
        "priority",
    ];

    protected $casts = [
        "due_at" => "datetime",
        "due_all_day" => "boolean",
        "completed_at" => "datetime",
    ];

    /**
     * Исполнитель — кому поручена задача (историческое имя поля user_id).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Синоним для читаемости: исполнитель задачи. */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    /** Автор — кто создал задачу. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by_user_id");
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Задачи, видимые пользователю: управляющие видят все, остальные — где
     * они исполнитель или автор (переданная другому задача остаётся у автора).
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw("1 = 0");
        }

        if ($user->hasRole(["admin", "master"])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where("user_id", $user->id)->orWhere("created_by_user_id", $user->id);
        });
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull("completed_at");
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull("completed_at");
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }
}
