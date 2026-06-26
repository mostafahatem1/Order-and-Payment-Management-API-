<?php

namespace Modules\Payments\Tests\Unit;

require_once __DIR__.'/../Support/ModuleTestSupport.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Payments\Gateways\CreditCardGateway;
use Modules\Payments\Gateways\PaymentGatewayFactory;
use Modules\Payments\Gateways\PaypalGateway;
use Modules\Payments\Models\Payment;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_card_gateway_returns_valid_response(): void
    {
        $response = app(CreditCardGateway::class)->charge(100, ['card_token' => 'tok_test']);

        $this->assertTrue($response['success']);
        $this->assertMatchesRegularExpression('/^CC_[A-F0-9]{16}$/', $response['transaction_id']);
    }

    public function test_paypal_gateway_returns_valid_response(): void
    {
        $response = app(PaypalGateway::class)->charge(100, ['paypal_order_id' => 'paypal-test']);

        $this->assertTrue($response['success']);
        $this->assertMatchesRegularExpression('/^PP_[A-F0-9]{16}$/', $response['transaction_id']);
    }

    public function test_factory_returns_correct_gateway_instance(): void
    {
        $factory = app(PaymentGatewayFactory::class);

        $this->assertInstanceOf(CreditCardGateway::class, $factory->make(Payment::METHOD_CREDIT_CARD));
        $this->assertInstanceOf(PaypalGateway::class, $factory->make(Payment::METHOD_PAYPAL));
    }

    public function test_factory_throws_exception_for_invalid_method(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment method.');

        app(PaymentGatewayFactory::class)->make('cash');
    }
}
