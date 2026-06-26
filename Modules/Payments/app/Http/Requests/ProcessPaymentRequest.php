<?php

namespace Modules\Payments\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Payments\Models\Payment;

class ProcessPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string', Rule::in([
                Payment::METHOD_CREDIT_CARD,
                Payment::METHOD_PAYPAL,
                Payment::METHOD_PAYMOB,
            ])],
            'gateway_data' => ['sometimes', 'array'],
        ];
    }
}