<?php

namespace Database\Factories\Modules\Users\Models {
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Illuminate\Support\Facades\Hash;
    use Modules\Users\Models\User;

    class UserFactory extends Factory
    {
        protected $model = User::class;

        public function definition(): array
        {
            return [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('Password1'),
            ];
        }
    }
}

namespace Database\Factories\Modules\Orders\Models {
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Modules\Orders\Models\Order;
    use Modules\Users\Models\User;

    class OrderFactory extends Factory
    {
        protected $model = Order::class;

        public function definition(): array
        {
            return [
                'user_id' => User::factory(),
                'total_amount' => fake()->randomFloat(2, 25, 500),
                'status' => Order::STATUS_PENDING,
            ];
        }

        public function confirmed(): static
        {
            return $this->state(fn (): array => [
                'status' => Order::STATUS_CONFIRMED,
            ]);
        }
    }
}

namespace Database\Factories\Modules\Orders\Models {
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Modules\Orders\Models\Order;
    use Modules\Orders\Models\OrderItem;

    class OrderItemFactory extends Factory
    {
        protected $model = OrderItem::class;

        public function definition(): array
        {
            return [
                'order_id' => Order::factory(),
                'product_name' => fake()->words(2, true),
                'quantity' => fake()->numberBetween(1, 5),
                'price' => fake()->randomFloat(2, 10, 150),
            ];
        }
    }
}

namespace Database\Factories\Modules\Payments\Models {
    use Illuminate\Database\Eloquent\Factories\Factory;
    use Modules\Orders\Models\Order;
    use Modules\Payments\Models\Payment;
    use Modules\Users\Models\User;

    class PaymentFactory extends Factory
    {
        protected $model = Payment::class;

        public function definition(): array
        {
            return [
                'order_id' => Order::factory(),
                'user_id' => User::factory(),
                'transaction_id' => 'TX_'.$this->faker->unique()->bothify('########'),
                'amount' => fake()->randomFloat(2, 25, 500),
                'status' => Payment::STATUS_SUCCESSFUL,
                'payment_method' => Payment::METHOD_CREDIT_CARD,
            ];
        }
    }
}

namespace Modules\Payments\Tests\Support {
    use Modules\Users\Models\User;

    trait ModuleTestSupport
    {
        protected function authHeaders(?User $user = null): array
        {
            $user ??= User::factory()->create();

            return [
                'Authorization' => 'Bearer '.auth('api')->login($user),
            ];
        }

        protected function validOrderPayload(): array
        {
            return [
                'items' => [
                    [
                        'product_name' => 'Keyboard',
                        'quantity' => 2,
                        'price' => 49.99,
                    ],
                    [
                        'product_name' => 'Mouse',
                        'quantity' => 1,
                        'price' => 25.50,
                    ],
                ],
            ];
        }
    }
}
