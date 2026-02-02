<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeImage extends Model
{
    protected $fillable = [
        'knowledge_base_id',
        'path',
        'alt',
    ];

    public function article()
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }
}
