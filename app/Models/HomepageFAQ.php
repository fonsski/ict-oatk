<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HomepageFAQ extends Model
{
    protected $table = 'homepage_faqs';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'markdown',
        'content',
        'is_active',
        'sort_order',
        'author_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($faq) {
            if (empty($faq->slug)) {
                $faq->slug = Str::slug($faq->title);
            }
            if (is_null($faq->sort_order)) {
                $faq->sort_order = static::max('sort_order') + 1;
            }
        });

        static::updating(function ($faq) {
            if (empty($faq->slug)) {
                $faq->slug = Str::slug($faq->title);
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Scope для активных FAQ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope для сортировки
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // Получить отрывок текста
    public function getExcerptAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        // Если excerpt пустой, создаем его из content
        if (!empty($this->content)) {
            return Str::limit(strip_tags($this->content), 150);
        }

        return '';
    }

    // URL для просмотра FAQ
    public function getUrlAttribute()
    {
        return route('homepage-faq.show', $this->slug);
    }
}
