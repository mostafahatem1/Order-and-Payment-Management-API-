<?php

namespace Modules\Payments\Gateways\Contracts;

interface PaymentGatewayInterface
{
    public function charge(float $amount, array $details): array;
}
