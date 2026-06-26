<?php

namespace Modules\Payments\Tests\Unit;

require_once __DIR__.'/../Support/ModuleTestSupport.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Orders\Models\Order;
use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;
use Modules\Payments\Gateways\PaymentGatewayFactory;
use Modules\Payments\Models\Payment;
use Modules\Payments\Services\PaymentService;
use Modules\Users\Models\User;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_payment_executes_correctly(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create([
            'total_amount' => 250,
        ]);

        $service = new PaymentService($this->gatewayFactoryReturning([
            'success' => true,
            'transaction_id' => 'UNIT_TX_1',
        ]));

        $payment = $service->processPayment($order->id, Payment::METHOD_CREDIT_CARD, ['card_token' => 'tok_test']);

        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame('UNIT_TX_1', $payment->transaction_id);
        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->status);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCESSFUL,
        ]);
    }

    public function test_db_transaction_rolls_back_when_gateway_fails(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andThrow(new RuntimeException('Gateway unavailable.'));

        $factory = Mockery::mock(PaymentGatewayFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(Payment::METHOD_PAYPAL)
            ->andReturn($gateway);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gateway unavailable.');

        try {
            (new PaymentService($factory))->processPayment($order->id, Payment::METHOD_PAYPAL, []);
        } finally {
            $this->assertDatabaseMissing('payments', [
                'order_id' => $order->id,
            ]);

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => Order::STATUS_CONFIRMED,
            ]);
        }
    }

    public function test_order_query_uses_lock_for_update(): void
    {
        $source = file_get_contents((new \ReflectionClass(PaymentService::class))->getFileName());

        $this->assertStringContainsString('->lockForUpdate()', $source);
    }

    public function test_order_status_updates_after_successful_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $service = new PaymentService($this->gatewayFactoryReturning([
            'success' => true,
            'transaction_id' => 'UNIT_TX_2',
        ]));

        $service->processPayment($order->id, Payment::METHOD_PAYPAL, []);

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
    }

    private function gatewayFactoryReturning(array $response): PaymentGatewayFactory
    {
        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn($response);

        $factory = Mockery::mock(PaymentGatewayFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->andReturn($gateway);

        return $factory;
    }
}
