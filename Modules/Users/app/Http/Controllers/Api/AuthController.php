<?php

namespace Modules\Users\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Users\Data\AuthenticatedUserData;
use Modules\Users\Data\UserData;
use Modules\Users\Http\Requests\LoginRequest;
use Modules\Users\Http\Requests\RegisterRequest;
use Modules\Users\Services\AuthService;
use Modules\Users\Services\TokenService;
use Modules\Users\Support\ApiResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TokenService $tokenService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success(
            'User registered successfully.',
            AuthenticatedUserData::from($result, $this->tokenService->authorizationPayload($result->token)),
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        if ($data === null) {
            return ApiResponse::error('Invalid credentials.', ['credentials' => ['Invalid credentials.']], 401);
        }

        return ApiResponse::success(
            'User logged in successfully.',
            AuthenticatedUserData::from($data, $this->tokenService->authorizationPayload($data->token))
        );
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success('User logged out successfully.');
    }

    public function me(): JsonResponse
    {
        return ApiResponse::success(
            'Authenticated user retrieved successfully.',
            UserData::from($this->authService->user())
        );
    }
}
