<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrawingCanvas extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "title",
        "slug",
        "description",
        "canvas_data",
        "type",
        "author_id",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "canvas_data" => "json",
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return "slug";
    }

    /**
     * Get the user that created the drawing.
     */
    public function author()
    {
        return $this->belongsTo(User::class, "author_id");
    }

    /**
     * Get the URL for this drawing
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route("drawing-canvas.show", $this->slug);
    }

    /**
     * Scope a query to include only drawings that the user can view.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isTechnician() || $user->isMaster()) {
            return $query;
        }

        return $query->where("author_id", $user->id);
    }
}
