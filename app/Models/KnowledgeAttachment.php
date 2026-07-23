<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAttachment extends Model
{
    protected $fillable = [
        'knowledge_base_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }

    /**
     * Размер файла в читаемом виде.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['Б', 'КБ', 'МБ', 'ГБ'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }
}
