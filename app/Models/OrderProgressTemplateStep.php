<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProgressTemplateStep extends Model
{
    protected $fillable = ['order_progress_template_id', 'label', 'sort_order'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OrderProgressTemplate::class, 'order_progress_template_id');
    }
}
