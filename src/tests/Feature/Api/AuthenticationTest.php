<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_valid_token_returns_user_and_token_information()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/test-token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'uername',
                ],
                'token' => [
                    'name',
                    'created_at',
                    'expires_at',
                    'last_used_at',
                ],
                'request_time',
            ])
            ->assertJson([
                'message' => 'Token is valid',
                'user' => [
                    'uername' => $user->name,
                ],
                'token' => [
                    'name' => 'test-token',
                ],
            ]);
    }

    #[Test]
    public function test_missing_token_returns_unauthorized()
    {
        $response = $this->getJson('/api/test-token');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_invalid_token_returns_unauthorized()
    {
        $response = $this->withToken('invalid-token-12345')
            ->getJson('/api/test-token');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_expired_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'], now()->subDay())->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/test-token');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function test_token_last_used_at_is_updated()
    {
        $user = User::factory()->create();
        $tokenModel = $user->createToken('test-token', ['*']);
        $token = $tokenModel->plainTextToken;

        $initialLastUsedAt = $tokenModel->accessToken->last_used_at;

        sleep(1);

        $this->withToken($token)
            ->getJson('/api/test-token');

        $tokenModel->accessToken->refresh();

        $this->assertGreaterThan(
            $initialLastUsedAt,
            $tokenModel->accessToken->last_used_at->timestamp
        );
    }
}
