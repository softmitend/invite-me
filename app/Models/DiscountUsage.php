<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsage extends Model
{
    protected $fillable = ['discount_id', 'order_id', 'user_id', 'amount'];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
