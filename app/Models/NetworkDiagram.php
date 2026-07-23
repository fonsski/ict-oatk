<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkDiagram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'author_id',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(NetworkNode::class, 'diagram_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'diagram_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
