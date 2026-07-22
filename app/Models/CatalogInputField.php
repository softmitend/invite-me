<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogInputField extends Model
{
    public const TYPES = ['short_text', 'long_text', 'date', 'time', 'select', 'checkbox', 'photo', 'document', 'url', 'audio'];

    protected $fillable = [
        'catalog_id',
        'label',
        'type',
        'placeholder',
        'help_text',
        'is_required',
        'sort_order',
        'max_file_size_kb',
        'options',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'options' => 'array'];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }
}
