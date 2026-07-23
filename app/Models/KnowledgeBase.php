<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = "draft";
    public const STATUS_PUBLISHED = "published";
    public const STATUS_ARCHIVED = "archived";

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
        "status",
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

    public function scopePublished($query)
    {
        return $query->where("status", self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where("status", self::STATUS_DRAFT);
    }

    public function scopeArchived($query)
    {
        return $query->where("status", self::STATUS_ARCHIVED);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Человекочитаемое название статуса.
     */
    public function getStatusLabelAttribute(): string
    {
        return [
            self::STATUS_DRAFT => "Черновик",
            self::STATUS_PUBLISHED => "Опубликована",
            self::STATUS_ARCHIVED => "В архиве",
        ][$this->status] ?? $this->status;
    }
}
