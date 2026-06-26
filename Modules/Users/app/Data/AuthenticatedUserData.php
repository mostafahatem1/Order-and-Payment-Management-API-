<?php

namespace Modules\Users\Data;

class AuthenticatedUserData
{
    public static function from(AuthResult $result, array $authorization): array
    {
        return [
            'user' => UserData::from($result->user),
            'authorization' => $authorization,
        ];
    }
}
