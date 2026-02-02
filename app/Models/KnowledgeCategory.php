<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeCategory extends Model
{
    protected $fillable = [
        "name",
        "slug",
        "description",
        "icon",
        "color",
        "sort_order",
        "is_active",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "sort_order" => "integer",
    ];

    
     * Связь с базой знаний

    public function knowledgeBase()
    {
        return $this->hasMany(KnowledgeBase::class, "category_id");
    }

    
     * Scope для активных категорий

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    
     * Scope для сортировки по порядку

    public function scopeOrdered($query)
    {
        return $query->orderBy("sort_order");
    }
}
