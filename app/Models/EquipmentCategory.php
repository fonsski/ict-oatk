<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCategory extends Model
{
    use HasFactory;

    
     * The attributes that are mass assignable.
     *
     * @var array

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    
     * Get the equipment items for the category.

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'category_id');
    }

    
     * Get the equipment count for this category

    public function getEquipmentCountAttribute(): int
    {
        return $this->equipment()->count();
    }

    
     * Scope for search by name

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                     ->orWhere('description', 'like', "%{$search}%");
    }

    
     * Convert the model to an array.
     *
     * @return array

    public function toArray()
    {
        $array = parent::toArray();
        $array['equipment_count'] = $this->equipment_count;

        return $array;
    }
}
