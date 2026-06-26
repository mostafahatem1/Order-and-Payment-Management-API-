<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): array
    {
        return [
            'success' => true,
            'transaction_id' => 'CC_'.strtoupper(bin2hex(random_bytes(8))),
        ];
    }
}
