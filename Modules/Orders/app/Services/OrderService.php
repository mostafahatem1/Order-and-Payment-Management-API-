<?php

namespace Modules\Orders\Services;

use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\Order;

class OrderService
{
    public function paginateForUser(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->where('user_id', $userId)
            ->filterByStatus($status)
            ->latest()
            ->paginate($perPage);
    }

    public function createOrder(int $userId, array $items): Order
    {
        return DB::transaction(function () use ($userId, $items): Order {
            $order = Order::create([
                'user_id' => $userId,
                'total_amount' => $this->calculateTotal($items),
                'status' => Order::STATUS_PENDING,
            ]);

            $order->items()->createMany(
                array_map(
                    fn (array $item): array => Arr::only($item, ['product_name', 'quantity', 'price']),
                    $items
                )
            );

            return $order->load(['items', 'payments']);
        });
    }

    public function updateOrder(int $id, array $data): Order
    {
        $order = Order::findOrFail($id);

        $order->update(Arr::only($data, [
            'user_id',
            'total_amount',
            'status',
        ]));

        return $order->refresh();
    }

    public function deleteOrder(int $id): bool
    {
        $order = Order::withCount('payments')->findOrFail($id);

        if ($order->payments_count > 0) {
            throw new DomainException('Cannot delete an order that has payments.');
        }

        return (bool) $order->delete();
    }

    private function calculateTotal(array $items): float
    {
        return array_reduce(
            $items,
            fn (float $total, array $item): float => $total + ((int) $item['quantity'] * (float) $item['price']),
            0.0
        );
    }
}
