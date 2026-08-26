<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = ["name", "slug", "sort_order"];

    protected $casts = [
        "sort_order" => "integer",
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy("sort_order")->orderBy("name");
    }

    /**
     * slug => name для селектов и валидации.
     */
    public static function options(): array
    {
        return static::ordered()->pluck("name", "slug")->toArray();
    }
}
