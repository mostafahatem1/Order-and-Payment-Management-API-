<?php

namespace Modules\Payments\Http\Controllers\Api;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Payments\Http\Requests\ProcessPaymentRequest;
use Modules\Payments\Http\Resources\PaymentResource;
use Modules\Payments\Services\PaymentService;
use Modules\Users\Support\ApiResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->paginateForUser(
            auth('api')->id(),
            $request->filled('order_id') ? $request->integer('order_id') : null,
            $request->integer('per_page', 15)
        );

        return ApiResponse::success('Payments retrieved successfully.', PaymentResource::collection($payments));
    }

    public function process(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment(
                $request->integer('order_id'),
                $request->validated('payment_method'),
                $request->validated('gateway_data') ?? []
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error('Order not found.', ['order' => ['Order not found.']], 404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), ['payment' => [$exception->getMessage()]], 422);
        }

        return ApiResponse::success('Payment processed successfully.', PaymentResource::make($payment));
    }
}
