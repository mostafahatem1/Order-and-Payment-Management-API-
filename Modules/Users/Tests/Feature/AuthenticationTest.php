<?php

namespace Modules\Users\Tests\Feature;

require_once __DIR__ . '/../../../Payments/Tests/Support/ModuleTestSupport.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Tests\Support\ModuleTestSupport;
use Modules\Users\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use ModuleTestSupport;
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mostafa Ahmed',
            'email' => 'mostafa@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'mostafa@example.com')
            ->assertJsonPath('data.authorization.type', 'bearer')
            ->assertJsonStructure([
                'data' => [
                    'authorization' => [
                        'token',
                        'type',
                        'expires_in',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'mostafa@example.com',
        ]);
    }

    public function test_user_can_login_and_receive_jwt_token(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'Password1',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'user@example.com')
            ->assertJsonPath('data.authorization.type', 'bearer')
            ->assertJsonStructure([
                'data' => [
                    'authorization' => [
                        'token',
                        'type',
                        'expires_in',
                    ],
                ],
            ]);
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/v1/auth/me', $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $user->email);
    }
}
