<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_login_returns_a_sanctum_token_and_profile_for_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'safe-test-password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'safe-test-password',
        ])->assertOk();

        $this->assertNotEmpty($response->json('token'));

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_rejects_invalid_credentials_without_creating_a_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_registration_assigns_only_the_default_viewer_role(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'New User',
            'email' => 'new-user@example.test',
            'password' => 'safe-test-password',
            'password_confirmation' => 'safe-test-password',
        ])
            ->assertCreated()
            ->assertJsonMissingPath('user.password');

        $user = User::where('email', 'new-user@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('viewer'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_forgot_password_does_not_disclose_unknown_email_addresses(): void
    {
        $this->postJson('/api/v1/forgot-password', [
            'email' => 'unknown-user@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset link sent successfully.');
    }

    public function test_password_reset_revokes_existing_api_tokens(): void
    {
        $user = User::factory()->create();
        $resetToken = Password::createToken($user);
        $user->createToken('existing')->plainTextToken;

        $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'new-safe-password',
            'password_confirmation' => 'new-safe-password',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('new-safe-password', $user->fresh()->password));
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
