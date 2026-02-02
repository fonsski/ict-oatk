<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = [
        "title",
        "slug",
        "category_id",
        "excerpt",
        "markdown",
        "content",
        "tags",
        "views_count",
        "author_id",
        "published_at",
    ];

    protected $casts = [
        "published_at" => "datetime",
        "views_count" => "integer",
    ];

    public function images()
    {
        return $this->hasMany(KnowledgeImage::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, "author_id");
    }

    public function category()
    {
        return $this->belongsTo(KnowledgeCategory::class, "category_id");
    }
}
