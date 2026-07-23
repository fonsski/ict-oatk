<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    /**
     * URL статей строятся по slug, а не по id.
     */
    public function getRouteKeyName(): string
    {
        return "slug";
    }

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
