<?php

use Illuminate\Support\Facades\Broadcast;

// Общий канал персонала: события заявок получают все сотрудники
// (аккаунты в системе есть только у них).
Broadcast::channel('staff', function ($user) {
    return $user->role
        && in_array($user->role->slug, ['admin', 'master', 'technician']);
});

// Персональный канал пользователя для его собственных уведомлений.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
