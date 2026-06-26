<?php

namespace Modules\Payments\Tests\Feature;

require_once __DIR__.'/../Support/ModuleTestSupport.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Orders\Models\Order;
use Modules\Payments\Gateways\Contracts\PaymentGatewayInterface;
use Modules\Payments\Gateways\PaymentGatewayFactory;
use Modules\Payments\Models\Payment;
use Modules\Payments\Tests\Support\ModuleTestSupport;
use Modules\Users\Models\User;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use ModuleTestSupport;
    use RefreshDatabase;

    public function test_process_payment_requires_authentication(): void
    {
        $order = Order::factory()->confirmed()->create();

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_process_credit_card_payment_successfully(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create([
            'total_amount' => 150,
        ]);

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
            'gateway_data' => ['card_token' => 'tok_test'],
        ], $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCESSFUL)
            ->assertJsonPath('data.payment_method', Payment::METHOD_CREDIT_CARD);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 150,
            'status' => Payment::STATUS_SUCCESSFUL,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_COMPLETED,
        ]);
    }

    public function test_process_paypal_payment_successfully(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_PAYPAL,
            'gateway_data' => ['paypal_order_id' => 'paypal-test'],
        ], $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCESSFUL)
            ->assertJsonPath('data.payment_method', Payment::METHOD_PAYPAL);
    }

    public function test_payment_is_rejected_when_order_status_is_not_confirmed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => Order::STATUS_PENDING,
        ]);

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
        ], $this->authHeaders($user))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Payment is only allowed for confirmed orders.');

        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
        ]);
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
        ], $this->authHeaders($user))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('payment_method', 'errors');
    }

    public function test_payment_failure_creates_failed_record_and_keeps_order_confirmed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $gateway = Mockery::mock(PaymentGatewayInterface::class);
        $gateway->shouldReceive('charge')
            ->once()
            ->andReturn([
                'success' => false,
                'transaction_id' => 'FAILED_TX_1',
            ]);

        $factory = Mockery::mock(PaymentGatewayFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(Payment::METHOD_CREDIT_CARD)
            ->andReturn($gateway);

        $this->app->instance(PaymentGatewayFactory::class, $factory);

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
        ], $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('data.status', Payment::STATUS_FAILED);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'transaction_id' => 'FAILED_TX_1',
            'status' => Payment::STATUS_FAILED,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CONFIRMED,
        ]);
    }

    public function test_duplicate_successful_payment_attempt_is_prevented(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->confirmed()->for($user)->create();

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_PAYPAL,
        ], $this->authHeaders($user))
            ->assertOk();

        $this->postJson('/api/v1/payments/process', [
            'order_id' => $order->id,
            'payment_method' => Payment::METHOD_PAYPAL,
        ], $this->authHeaders($user))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Payment is only allowed for confirmed orders.');

        $this->assertDatabaseCount('payments', 1);
    }
}
