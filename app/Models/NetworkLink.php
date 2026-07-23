<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkLink extends Model
{
    protected $fillable = [
        'diagram_id',
        'source_id',
        'target_id',
        'label',
    ];

    public function diagram(): BelongsTo
    {
        return $this->belongsTo(NetworkDiagram::class, 'diagram_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'source_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'target_id');
    }
}
