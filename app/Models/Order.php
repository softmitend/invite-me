<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_EXPIRED = 'expired';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    public const WORK_RECEIVED = 'received';
    public const WORK_IN_PROGRESS = 'in_progress';
    public const WORK_PREVIEW = 'preview';
    public const WORK_REVISION = 'revision';
    public const WORK_COMPLETED = 'completed';
    public const WORK_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'discount_total',
        'total',
        'deposit_amount',
        'paid_amount',
        'payment_status',
        'work_status',
        'preview_url',
        'preview_token',
        'final_url',
        'preview_approved_at',
    ];

    protected function casts(): array
    {
        return ['preview_approved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inputValues(): HasMany
    {
        return $this->hasMany(OrderInputValue::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function progressSteps(): HasMany
    {
        return $this->hasMany(OrderProgressStep::class)->orderBy('sort_order');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderRevision::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
