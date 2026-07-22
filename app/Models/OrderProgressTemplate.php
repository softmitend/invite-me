<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProgressTemplate extends Model
{
    protected $fillable = ['name', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(OrderProgressTemplateStep::class)->orderBy('sort_order');
    }
}
