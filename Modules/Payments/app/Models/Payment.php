<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Models\Order;
use Modules\Users\Models\User;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const METHOD_CREDIT_CARD = 'credit_card';

    public const METHOD_PAYPAL = 'paypal';

    public const METHOD_PAYMOB = 'paymob';

    protected $fillable = [
        'order_id',
        'user_id',
        'transaction_id',
        'amount',
        'status',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}