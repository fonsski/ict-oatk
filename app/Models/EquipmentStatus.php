<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentStatus extends Model
{
    protected $fillable = ['name', 'slug'];

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'status_id');
    }
}
