<?php

namespace App\Models;

use Database\Factories\CatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalog extends Model
{
    /** @use HasFactory<CatalogFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'discount_id',
        'name',
        'slug',
        'description',
        'preview_url',
        'base_price',
        'estimated_days',
        'rating_average',
        'reviews_count',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'estimated_days' => 'integer',
            'rating_average' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CatalogImage::class)->orderBy('sort_order');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(CatalogSpecification::class)->orderBy('sort_order');
    }

    public function inputFields(): HasMany
    {
        return $this->hasMany(CatalogInputField::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
