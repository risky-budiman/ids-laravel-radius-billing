<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registrasi publik sengaja dimatikan: akun staf hanya dibuat administrator
 * (Manage Staff). Test ini memastikan tidak ada jalur pendaftaran mandiri.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_users_cannot_self_register(): void
    {
        $this->post('/admin/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);
    }
}
