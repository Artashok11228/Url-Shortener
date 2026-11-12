<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'demo@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonStructure(['token', 'token_type', 'data' => ['id', 'name', 'email', 'role']]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_invalid_credentials_without_revealing_which_field_failed(): void
    {
        User::factory()->create(['email' => 'demo@example.com', 'password' => 'password']);

        $this->postJson('/api/login', [
            'email' => 'demo@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/login', [
            'email' => 'missing@example.com',
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_view_me_and_logout_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'demo@example.com',
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'demo@example.com');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Feature tests reuse one app instance; clear the guard so the next request
        // must authenticate from the (now deleted) Bearer token again.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_guest_cannot_access_protected_auth_endpoints(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_me_works_with_sanctum_acting_as_for_test_setups(): void
    {
        Sanctum::actingAs(User::factory()->create(['email' => 'acting@example.com']));

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'acting@example.com');
    }
}
