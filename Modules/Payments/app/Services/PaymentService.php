<?php

namespace Modules\Payments\Services;

use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\Order;
use Modules\Payments\Gateways\PaymentGatewayFactory;
use Modules\Payments\Models\Payment;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory
    ) {}

    public function processPayment(int $orderId, string $method, array $gatewayData): Payment
    {
        return DB::transaction(function () use ($orderId, $method, $gatewayData): Payment {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            if ($order->status !== Order::STATUS_CONFIRMED) {
                throw new DomainException('Payment is only allowed for confirmed orders.');
            }

            $result = $this->gatewayFactory
                ->make($method)
                ->charge((float) $order->total_amount, $gatewayData);

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'transaction_id' => $result['transaction_id'],
                'amount' => $order->total_amount,
                'status' => $result['success'] ? Payment::STATUS_SUCCESSFUL : Payment::STATUS_FAILED,
                'payment_method' => $method,
            ]);

            if ($result['success']) {
                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                ]);
            }

            return $payment;
        });
    }

    public function paginateForUser(int $userId, ?int $orderId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->where('user_id', $userId)
            ->when($orderId !== null, fn ($query) => $query->where('order_id', $orderId))
            ->latest()
            ->paginate($perPage);
    }
}
