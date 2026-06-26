<?php

namespace Modules\Users\Data;

use Modules\Users\Models\User;

readonly class AuthResult
{
    public function __construct(
        public User $user,
        public string $token
    ) {}
}
