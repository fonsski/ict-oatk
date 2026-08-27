<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmcRequestItem extends Model
{
    protected $fillable = [
        "tmc_request_id",
        "name",
        "quantity",
        "unit",
        "unit_price",
        "sum",
    ];

    protected $casts = [
        "quantity" => "integer",
        "unit_price" => "decimal:2",
        "sum" => "decimal:2",
    ];

    public function tmcRequest(): BelongsTo
    {
        return $this->belongsTo(TmcRequest::class);
    }
}
