<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** Fulfilment states. Payment moves an order to `new`, never past it. */
    public const STATUSES = [
        'pending_payment' => 'Awaiting payment',
        'new' => 'New',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'payment_failed' => 'Payment failed',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'reference',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'notes',
        'subtotal_kobo',
        'delivery_fee_kobo',
        'total_kobo',
        'status',
        'payment_status',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'subtotal_kobo' => 'integer',
        'delivery_fee_kobo' => 'integer',
        'total_kobo' => 'integer',
        'paid_at' => 'datetime',
    ];

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
