<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_FINAL = 'final_payment';
    public const TYPE_FULL = 'full_payment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'payment_number',
        'midtrans_order_id',
        'snap_token',
        'snap_redirect_url',
        'type',
        'status',
        'amount',
        'payment_method',
        'transaction_id',
        'paid_at',
        'expires_at',
        'notification_payload',
        'notification_hash',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'expires_at' => 'datetime', 'notification_payload' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
