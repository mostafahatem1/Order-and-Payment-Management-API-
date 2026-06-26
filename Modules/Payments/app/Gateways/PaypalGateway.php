<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;

class PaypalGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): array
    {
        return [
            'success' => true,
            'transaction_id' => 'PP_'.strtoupper(bin2hex(random_bytes(8))),
        ];
    }
}
