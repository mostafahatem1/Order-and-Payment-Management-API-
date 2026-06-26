<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;

class PaymobGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): array
    {
        $config = config('payments.gateways.paymob.config', []);

        return [
            'success' => true,
            'transaction_id' => 'PM_'.strtoupper(bin2hex(random_bytes(8))),
            'redirect_url' => $this->checkoutUrl($details, $config),
        ];
    }

    private function checkoutUrl(array $details, array $config): string
    {
        $sessionId = $details['session_id'] ?? strtolower(bin2hex(random_bytes(8)));
        $iframeId = $config['iframe_id'] ?? null;

        if ($iframeId) {
            return "https://paymob-sandbox/checkout/{$iframeId}/{$sessionId}";
        }

        return "https://paymob-sandbox/checkout/{$sessionId}";
    }
}
