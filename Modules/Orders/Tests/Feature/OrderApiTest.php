<?php

namespace Modules\Orders\Tests\Feature;

require_once __DIR__.'/../../../Payments/Tests/Support/ModuleTestSupport.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\Order;
use Modules\Payments\Models\Payment;
use Modules\Payments\Tests\Support\ModuleTestSupport;
use Modules\Users\Models\User;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use ModuleTestSupport;
    use RefreshDatabase;

    public function test_create_order_requires_authentication(): void
    {
        $this->postJson('/api/v1/orders', $this->validOrderPayload())
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_create_order(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/orders', $this->validOrderPayload(), $this->authHeaders($user));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', Order::STATUS_PENDING);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 125.48,
        ]);

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_authenticated_user_can_update_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->putJson('/api/v1/orders/'.$order->id, [
            'status' => Order::STATUS_CONFIRMED,
            'total_amount' => 199.95,
        ], $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Order::STATUS_CONFIRMED);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CONFIRMED,
            'total_amount' => 199.95,
        ]);
    }

    public function test_authenticated_user_can_delete_order_without_payments(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->deleteJson('/api/v1/orders/'.$order->id, [], $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_delete_order_is_prevented_when_payments_exist(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        Payment::factory()->for($order)->for($user)->create([
            'amount' => $order->total_amount,
        ]);

        $this->deleteJson('/api/v1/orders/'.$order->id, [], $this->authHeaders($user))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot delete an order that has payments.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_authenticated_user_can_get_orders_list(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->for($user)->create();
        Order::factory()->create();

        $this->getJson('/api/v1/orders', $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        foreach ([Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED] as $status) {
            Order::factory()->for($user)->create([
                'status' => $status,
            ]);
        }

        $this->getJson('/api/v1/orders?status='.Order::STATUS_CONFIRMED, $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', Order::STATUS_CONFIRMED)
            ->assertJsonPath('meta.total', 1);
    }
}
