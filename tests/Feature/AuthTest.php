<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_user_can_log_in_and_receive_a_token(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $token = User::factory()->create()->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_gets_json_401_on_protected_routes(): void
    {
        $this->getJson('/api/v1/books')->assertUnauthorized();
        $this->get('/api/v1/books')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }
}
