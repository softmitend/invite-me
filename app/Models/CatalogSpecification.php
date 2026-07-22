<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogSpecification extends Model
{
    protected $fillable = ['catalog_id', 'label', 'value', 'sort_order'];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }
}
