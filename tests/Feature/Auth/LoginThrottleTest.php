<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batas percobaan login tidak bisa ditembus dengan memalsukan X-Forwarded-For
 * atau berganti-ganti IP.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $email, string $password, string $remoteAddr, ?string $forwardedFor = null)
    {
        $req = $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr]);
        if ($forwardedFor) {
            $req = $req->withHeader('X-Forwarded-For', $forwardedFor);
        }

        return $req->from(route('login'))->post(route('login'), ['login' => $email, 'password' => $password]);
    }

    public function test_spoofed_forwarded_for_does_not_reset_the_per_ip_limit(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        for ($i = 1; $i <= 5; $i++) {
            $this->attempt($user->email, 'salah', '203.0.113.7', "10.9.9.$i");
        }

        // Password benar pun ditolak: IP penyerang (bukan header palsu) sudah mencapai batas
        $this->attempt($user->email, 'password', '203.0.113.7', '10.9.9.99')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_account_is_locked_after_many_failures_from_different_ips(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        for ($i = 1; $i <= 20; $i++) {
            $this->attempt($user->email, 'salah', "198.51.100.$i");
        }

        $this->attempt($user->email, 'password', '198.51.100.200')->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
