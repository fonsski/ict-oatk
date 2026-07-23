<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkNode extends Model
{
    protected $fillable = [
        'diagram_id',
        'label',
        'type',
        'ip_address',
        'room_id',
        'pos_x',
        'pos_y',
    ];

    protected $casts = [
        'pos_x' => 'integer',
        'pos_y' => 'integer',
    ];

    /**
     * Допустимые типы узлов и их читаемые названия.
     */
    public const TYPES = [
        'internet' => 'Интернет',
        'router' => 'Маршрутизатор',
        'switch' => 'Коммутатор',
        'server' => 'Сервер',
        'workstation' => 'Рабочая станция',
        'access_point' => 'Точка доступа',
        'printer' => 'Принтер',
        'other' => 'Другое',
    ];

    public function diagram(): BelongsTo
    {
        return $this->belongsTo(NetworkDiagram::class, 'diagram_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
