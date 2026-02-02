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

    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    
    public function getExcerptAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        
        if (!empty($this->content)) {
            return Str::limit(strip_tags($this->content), 150);
        }

        return '';
    }

    
    public function getUrlAttribute()
    {
        return route('homepage-faq.show', $this->slug);
    }
}
