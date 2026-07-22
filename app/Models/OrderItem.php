<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'catalog_id',
        'catalog_name',
        'catalog_slug',
        'category_name',
        'unit_price',
        'discount_amount',
        'quantity',
        'line_total',
        'snapshot',
    ];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    public function inputValues(): HasMany
    {
        return $this->hasMany(OrderInputValue::class);
    }
}
