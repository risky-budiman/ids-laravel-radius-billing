<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Verifikasi dua langkah (TOTP) staf: aktivasi, login web & API, kode cadangan, wajib per role, reset. */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true, 'password' => 'rahasia-123']);
    }

    /** Aktifkan 2FA lewat halaman profil; mengembalikan [secret, kode cadangan]. */
    private function enable(User $user): array
    {
        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect();
        $secret = session('two_factor_setup');
        $this->assertNotEmpty($secret);
        $this->post(route('two-factor.confirm'), ['two_factor_code' => '000000'])->assertSessionHasErrors('two_factor_code');
        $res = $this->post(route('two-factor.confirm'), ['two_factor_code' => Totp::code($secret, Totp::currentStep())]);
        $res->assertSessionHas('two_factor_recovery');
        $codes = session('two_factor_recovery');
        auth()->logout();

        return [$secret, $codes];
    }

    public function test_totp_matches_rfc6238_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        foreach ([59 => '287082', 1111111109 => '081804', 1234567890 => '005924', 2000000000 => '279037'] as $t => $code) {
            $this->assertSame($code, Totp::code($secret, intdiv($t, 30)));
        }
        $this->assertSame(1, Totp::verify($secret, '287 082', 1, 59));          // spasi diabaikan, langkah dikembalikan
        $this->assertNull(Totp::verify($secret, '287082', 1, 59 + 120));       // terlalu lama
        $this->assertStringStartsWith('otpauth://totp/', Totp::uri($secret, 'a@b.c', 'PT Uji'));
    }

    public function test_enable_stores_encrypted_secret_and_never_logs_it(): void
    {
        $user = $this->staff();
        [$secret, $codes] = $this->enable($user);

        $user->refresh();
        $this->assertTrue($user->hasTwoFactor());
        $this->assertCount(8, $codes);
        $raw = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);                                    // terenkripsi
        $this->assertStringNotContainsString($codes[0], (string) DB::table('users')->where('id', $user->id)->value('two_factor_recovery_codes'));
        $this->assertTrue(ActivityLog::where('action', 'two_factor_enabled')->exists());
        foreach (ActivityLog::all() as $log) {
            $this->assertStringNotContainsString($secret, json_encode($log->properties) . $log->description);
        }
    }

    public function test_login_requires_code_codes_are_single_use_and_rate_limited(): void
    {
        $user = $this->staff();
        [$secret, $codes] = $this->enable($user);

        // Password benar → halaman kode, belum login
        $this->post(route('login'), ['login' => $user->email, 'password' => 'rahasia-123'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Verifikasi dua langkah');
        $this->assertFalse(ActivityLog::where('action', 'login')->where('user_id', $user->id)->exists());

        $this->post(route('two-factor.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertTrue(ActivityLog::where('action', 'two_factor_failed')->exists());

        // Kode untuk langkah berikutnya (toleransi ±1) diterima; kode yang sama tidak bisa dipakai ulang
        $code = Totp::code($secret, Totp::currentStep() + 1);
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        auth()->logout();
        $this->post(route('login'), ['login' => $user->email, 'password' => 'rahasia-123']);
        $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Kode cadangan: sekali pakai
        $this->post(route('two-factor.verify'), ['code' => strtolower($codes[0])])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, (array) $user->fresh()->two_factor_recovery_codes);
        auth()->logout();
        $this->post(route('login'), ['login' => $user->email, 'password' => 'rahasia-123']);
        $this->post(route('two-factor.verify'), ['code' => $codes[0]])->assertSessionHasErrors('code');

        // Batas percobaan
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.verify'), ['code' => '999999']);
        }
        $this->post(route('two-factor.verify'), ['code' => Totp::code($secret, Totp::currentStep())])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_pending_login_expires_and_wrong_password_never_reaches_code_page(): void
    {
        $user = $this->staff();
        $this->enable($user);
        $this->post(route('login'), ['login' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('login');
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));

        $this->post(route('login'), ['login' => $user->email, 'password' => 'rahasia-123']);
        $this->travel(11)->minutes();
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
    }

    public function test_api_login_requires_code(): void
    {
        $user = $this->staff('teknisi');
        [$secret] = $this->enable($user);
        $this->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'rahasia-123'])->assertStatus(202)->assertJson(['two_factor_required' => true]);
        $this->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'rahasia-123', 'code' => '111111'])->assertStatus(422);
        $this->postJson('/api/v1/admin/login', ['email' => $user->email, 'password' => 'rahasia-123', 'code' => Totp::code($secret, Totp::currentStep() + 1)])
            ->assertOk()->assertJsonStructure(['token']);

        $plain = $this->staff('teknisi');   // tanpa 2FA: tetap seperti dulu
        $this->postJson('/api/v1/admin/login', ['email' => $plain->email, 'password' => 'rahasia-123'])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_required_roles_disable_and_admin_reset(): void
    {
        $root = $this->staff('administrator');
        $kasir = $this->staff('kasir');
        $this->actingAs($root)->post(route('users.two-factor.required'), ['roles' => ['kasir']])->assertSessionHas('success');
        $this->assertSame(['kasir'], TwoFactorService::requiredRoles());
        $this->get(route('users.index'))->assertOk()->assertSee('Verifikasi dua langkah wajib untuk role');

        // Kasir tanpa 2FA hanya bisa membuka profil
        $this->actingAs($kasir)->get(route('dashboard'))->assertRedirect(route('profile.edit') . '#verifikasi-dua-langkah');
        $this->get(route('profile.edit'))->assertOk()->assertSee('Wajib — belum aktif');
        $this->getJson(route('dashboard'))->assertForbidden();

        [$secret] = $this->enable($kasir);
        $this->actingAs($kasir->fresh())->get(route('dashboard'))->assertOk();
        // Wajib → tidak bisa dinonaktifkan sendiri
        $this->post(route('two-factor.disable'), ['two_factor_password' => 'rahasia-123', 'two_factor_code' => Totp::code($secret, Totp::currentStep() + 1)])
            ->assertSessionHasErrors('two_factor_password');
        $this->assertTrue($kasir->fresh()->hasTwoFactor());

        // Administrator mereset (HP hilang); admin biasa tidak bisa
        $admin = $this->staff('admin');
        $this->actingAs($admin)->post(route('users.two-factor.reset', $kasir))->assertForbidden();
        $this->actingAs($root)->post(route('users.two-factor.reset', $kasir))->assertSessionHas('success');
        $this->assertFalse($kasir->fresh()->hasTwoFactor());
        $this->assertTrue(ActivityLog::where('action', 'two_factor_disabled')->exists());

        // Role tidak wajib: bisa menonaktifkan sendiri dengan password + kode
        [$s2] = $this->enable($admin);
        $this->actingAs($admin->fresh())->post(route('two-factor.disable'), ['two_factor_password' => 'salah', 'two_factor_code' => '000000'])->assertSessionHasErrors('two_factor_password');
        $this->post(route('two-factor.disable'), ['two_factor_password' => 'rahasia-123', 'two_factor_code' => Totp::code($s2, Totp::currentStep() + 1)])->assertSessionHas('success');
        $this->assertFalse($admin->fresh()->hasTwoFactor());
    }
}
