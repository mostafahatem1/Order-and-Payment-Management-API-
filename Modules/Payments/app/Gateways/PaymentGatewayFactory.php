<?php

namespace Modules\Payments\Gateways;

use InvalidArgumentException;
use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;

class PaymentGatewayFactory
{
    public function make(string $method): PaymentGatewayInterface
    {
        $gateways = config('payments.gateways', []);

        $gatewayConfig = $gateways[$method] ?? null;
        $driver = is_array($gatewayConfig) ? ($gatewayConfig['driver'] ?? null) : $gatewayConfig;

        $gateway = match (true) {
            is_string($driver) => app($driver),
            default => throw new InvalidArgumentException('Unsupported payment method.'),
        };

        if (! $gateway instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException('Invalid payment gateway configuration.');
        }

        return $gateway;
    }
}
