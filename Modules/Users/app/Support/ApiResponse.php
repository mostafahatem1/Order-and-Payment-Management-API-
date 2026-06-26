<?php

namespace Modules\Users\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(string $message, mixed $data = [], int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => self::resolveData($data),
        ];

        $meta = self::paginationMeta($data);

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private static function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(request());
        }

        return $data;
    }

    private static function paginationMeta(mixed $data): array
    {
        if (! $data instanceof ResourceCollection || ! $data->resource instanceof LengthAwarePaginator) {
            return [];
        }

        return [
            'current_page' => $data->resource->currentPage(),
            'last_page' => $data->resource->lastPage(),
            'per_page' => $data->resource->perPage(),
            'total' => $data->resource->total(),
        ];
    }
}
