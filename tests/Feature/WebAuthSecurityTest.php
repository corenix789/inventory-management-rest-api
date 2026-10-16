<?php

namespace Tests\Feature;

use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_dashboard_login_is_rate_limited(): void
    {
        $credentials = [
            'email' => 'unknown-user@example.test',
            'password' => 'invalid-password',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/dashboard/login', $credentials)->assertRedirect();
        }

        $this->post('/dashboard/login', $credentials)->assertTooManyRequests();
    }

    public function test_dashboard_logout_does_not_change_state_on_get(): void
    {
        $this->get('/dashboard/logout')->assertStatus(405);
    }

    public function test_dashboard_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/dashboard/register', [
                'name' => 'New Dashboard User',
                'email' => "new-dashboard-user-{$attempt}@example.test",
                'password' => 'safe-test-password',
                'password_confirmation' => 'safe-test-password',
            ])->assertRedirect('/dashboard/home');
        }

        $this->post('/dashboard/register', [
            'name' => 'New Dashboard User',
            'email' => 'last-dashboard-user@example.test',
            'password' => 'safe-test-password',
            'password_confirmation' => 'safe-test-password',
        ])->assertTooManyRequests();
    }
}
