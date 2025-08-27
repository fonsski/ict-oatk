<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name",
        "email",
        "phone", // оставляем поле, но делаем необязательным
        "password",
        "role_id",
        "is_active",
        "last_login_at",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Канал уведомлений по умолчанию для email
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    /**
     * Канал уведомлений по умолчанию для SMS
     *
     * @return string
     */
    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
        "is_active" => "boolean",
        "last_login_at" => "datetime",
        "phone" => "string",
        "phone_verified_at" => "datetime",
    ];

    /**
     * Отношение к роли пользователя
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Отношение к заявкам пользователя
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, "reporter_id");
    }

    /**
     * Отношение к назначенным заявкам (для техников и мастеров)
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, "assigned_to_id");
    }

    /**
     * Отношение к комментариям пользователя
     */
    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Отношение к кабинетам, за которые пользователь ответственен
     */
    public function responsibleForRooms()
    {
        return $this->hasMany(Room::class, "responsible_user_id");
    }

    /**
     * Проверка, имеет ли пользователь определенную роль
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->role && $this->role->slug === $role;
        }

        if (is_array($role)) {
            return $this->role && in_array($this->role->slug, $role);
        }

        return false;
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin()
    {
        return $this->hasRole("admin");
    }

    /**
     * Проверка, является ли пользователь мастером
     */
    public function isMaster()
    {
        return $this->hasRole("master");
    }

    /**
     * Проверка, является ли пользователь техником
     */
    public function isTechnician()
    {
        return $this->hasRole("technician");
    }

    /**
     * Проверка, может ли пользователь управлять заявками
     */
    public function canManageTickets()
    {
        return $this->hasRole(["admin", "master", "technician"]);
    }

    /**
     * Проверка, может ли пользователь управлять оборудованием
     */
    public function canManageEquipment()
    {
        return $this->hasRole(["admin", "master", "technician"]);
    }

    /**
     * Проверка, может ли пользователь управлять пользователями
     */
    public function canManageUsers()
    {
        return $this->hasRole(["admin", "master"]);
    }

    /**
     * Получение полного имени роли
     */
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->name : "Не назначена";
    }

    /**
     * Форматирует номер телефона перед отображением
     */
    public function getFormattedPhoneAttribute()
    {
        return format_phone($this->phone);
    }

    /**
     * Сохраняет номер телефона в нормализованном виде
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes["phone"] = clean_phone($value);
    }

    /**
     * Получение статуса активности в читаемом виде
     */
    public function getStatusTextAttribute()
    {
        return $this->is_active ? "Активен" : "Неактивен";
    }

    /**
     * Получение статуса активности в виде badge
     */
    public function getStatusBadgeAttribute()
    {
        $class = $this->is_active
            ? "bg-green-100 text-green-800"
            : "bg-red-100 text-red-800";
        $text = $this->is_active ? "Активен" : "Неактивен";

        return "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}'>{$text}</span>";
    }

    /**
     * Обновление времени последнего входа
     */
    public function updateLastLogin()
    {
        $this->update(["last_login_at" => now()]);
    }

    /**
     * Активация учетной записи
     */
    public function activate()
    {
        $this->update(["is_active" => true]);
    }

    /**
     * Деактивация учетной записи
     */
    public function deactivate()
    {
        $this->update(["is_active" => false]);
    }

    /**
     * Сброс пароля
     */
    public function resetPassword($newPassword)
    {
        $this->update(["password" => bcrypt($newPassword)]);
    }

    /**
     * Scope для активных пользователей
     */
    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope для неактивных пользователей
     */
    public function scopeInactive($query)
    {
        return $query->where("is_active", false);
    }

    /**
     * Scope для поиска по имени или телефону
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where("name", "like", "%{$search}%")->orWhere(
                "phone",
                "like",
                "%{$search}%",
            );
        });
    }

    /**
     * Отправка СМС для верификации телефона
     */
    public function sendPhoneVerificationNotification()
    {
        // В будущем можно будет реализовать отправку СМС с кодом подтверждения
        // $this->notify(new \App\Notifications\VerifyPhoneNotification);
    }

    /**
     * Scope для пользователей с определенной ролью
     */
    public function scopeWithRole($query, $roleId)
    {
        return $query->where("role_id", $roleId);
    }

    /**
     * Scope для пользователей, зарегистрированных за последние N дней
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where("created_at", ">=", now()->subDays($days));
    }
}
