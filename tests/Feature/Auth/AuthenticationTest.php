<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'administrator', 'is_active' => true], $attrs));
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke panel admin');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->staff();

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = $this->staff();

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_failed_login_keeps_the_entered_username(): void
    {
        $user = $this->staff();

        $this->from(route('login'))->post(route('login'), ['login' => $user->email, 'password' => 'salah'])
            ->assertRedirect(route('login'));

        $this->get(route('login'))->assertSee('value="' . $user->email . '"', false);
    }

    public function test_users_can_logout(): void
    {
        $user = $this->staff();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/admin');
    }
}
