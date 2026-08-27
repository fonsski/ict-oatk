<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmcRequest extends Model
{
    use HasDocuments;

    protected $fillable = [
        "number",
        "date",
        "purpose",
        "total_sum",
        "created_by_user_id",
    ];

    protected $casts = [
        "date" => "date",
        "total_sum" => "decimal:2",
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TmcRequestItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by_user_id");
    }

    public function recalculateTotal(): void
    {
        $this->update(["total_sum" => $this->items()->sum("sum")]);
    }
}
