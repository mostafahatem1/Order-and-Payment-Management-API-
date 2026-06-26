<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Orders\Models\Order;

class UpdateOrderRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in([
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
