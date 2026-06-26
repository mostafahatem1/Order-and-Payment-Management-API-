<?php

namespace Modules\Users\Services;

class TokenService
{
    public function authorizationPayload(string $token): array
    {
        return [
            'token' => $token,
            'type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
