<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RadiusCoAService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_portal_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login-client', ['customer_code' => 'X', 'phone' => '0']);
        }

        $this->post('/login-client', ['customer_code' => 'X', 'phone' => '0'])->assertStatus(429);
    }

    public function test_customer_api_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/customer/login', ['customer_code' => 'X', 'phone' => '0']);
        }

        $this->postJson('/api/v1/customer/login', ['customer_code' => 'X', 'phone' => '0'])->assertStatus(429);
    }

    public function test_admin_api_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/admin/login', ['email' => 'x@example.test', 'password' => 'salah']);
        }

        $this->postJson('/api/v1/admin/login', ['email' => 'x@example.test', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_realtime_test_endpoint_is_administrator_only_and_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.test-realtime'))->assertForbidden();

        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($administrator)->get('/admin/test-realtime')->assertStatus(405);
    }

    public function test_test_ping_route_removed(): void
    {
        $this->get('/test-ping')->assertNotFound();
    }

    public function test_coa_disconnect_runs_radclient_without_a_shell(): void
    {
        Process::fake();

        $ok = app(RadiusCoAService::class)->disconnect("10.0.0.1", "s3cret'; reboot #", 'user"; rm -rf / #', "sess'1");

        $this->assertTrue($ok);
        Process::assertRan(function (PendingProcess $process) {
            // Perintah berupa array argumen (tanpa shell); nilai berbahaya hanya jadi data.
            return $process->command === ['radclient', '-t', '2', '-x', '10.0.0.1:3799', 'disconnect', "s3cret'; reboot #"]
                && str_contains($process->input, 'User-Name = "user\"; rm -rf / #"')
                && str_contains($process->input, "Acct-Session-Id = \"sess'1\"");
        });
    }

    public function test_coa_disconnect_reports_failure(): void
    {
        Process::fake(fn () => Process::result(errorOutput: 'no response', exitCode: 1));

        $this->assertFalse(app(RadiusCoAService::class)->disconnect('10.0.0.1', 'secret', 'user1'));
    }
}
