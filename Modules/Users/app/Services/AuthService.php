<?php

namespace Modules\Users\Services;

use Illuminate\Support\Arr;
use Modules\Users\Data\AuthResult;
use Modules\Users\Models\User;

class AuthService
{
    public function register(array $data): AuthResult
    {
        $user = User::create(Arr::only($data, [
            'name',
            'email',
            'password',
        ]));

        return new AuthResult($user, auth('api')->login($user));
    }

    public function login(array $credentials): ?AuthResult
    {
        $token = auth('api')->attempt($credentials);

        if ($token === false) {
            return null;
        }

        return new AuthResult(auth('api')->user(), $token);
    }

    public function logout(): void
    {
        auth('api')->logout();
    }

    public function user(): User
    {
        return auth('api')->user();
    }
}
