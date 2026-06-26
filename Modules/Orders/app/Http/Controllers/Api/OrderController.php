<?php

namespace Modules\Orders\Http\Controllers\Api;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Orders\Http\Requests\StoreOrderRequest;
use Modules\Orders\Http\Requests\UpdateOrderRequest;
use Modules\Orders\Http\Resources\OrderCollection;
use Modules\Orders\Http\Resources\OrderResource;
use Modules\Orders\Services\OrderService;
use Modules\Users\Support\ApiResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->paginateForUser(
            auth('api')->id(),
            $request->string('status')->toString() ?: null,
            $request->integer('per_page', 15)
        );

        return ApiResponse::success('Orders retrieved successfully.', new OrderCollection($orders));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            auth('api')->id(),
            $request->validated('items')
        );

        return ApiResponse::success('Order created successfully.', OrderResource::make($order), 201);
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder($id, $request->validated());
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Order not found.', ['order' => ['Order not found.']], 404);
        }

        return ApiResponse::success('Order updated successfully.', OrderResource::make($order->load(['items', 'payments'])));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->orderService->deleteOrder($id);
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Order not found.', ['order' => ['Order not found.']], 404);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), ['order' => [$exception->getMessage()]], 422);
        }

        return ApiResponse::success('Order deleted successfully.');
    }
}
