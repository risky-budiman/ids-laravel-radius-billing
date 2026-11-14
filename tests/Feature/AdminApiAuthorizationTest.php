<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * API admin (/api/v1/admin/*) hanya untuk staf, dengan batasan role per kelompok endpoint.
 */
class AdminApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, bool $active = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $active]);
    }

    public function test_customer_token_cannot_access_admin_api(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-API-001',
            'username' => 'cust_api_001',
            'name' => 'Pelanggan API',
            'phone' => '081200000001',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->getJson('/api/v1/admin/customers')->assertForbidden();

        // Eskalasi hak akses: pelanggan mencoba membuat akun administrator.
        $this->postJson('/api/v1/admin/admin/users', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'rahasia123',
            'role' => 'administrator',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);
    }

    public static function forbiddenCases(): array
    {
        return [
            'kasir membuat user' => ['kasir', 'post', '/api/v1/admin/admin/users'],
            'kasir lihat pengaturan' => ['kasir', 'get', '/api/v1/admin/admin/settings'],
            'sales lihat pengaturan' => ['sales', 'get', '/api/v1/admin/admin/settings'],
            'admin lihat daftar user' => ['admin', 'get', '/api/v1/admin/admin/users'],
            'teknisi lihat jurnal' => ['teknisi', 'get', '/api/v1/admin/accounting/coa'],
            'teknisi lihat invoice' => ['teknisi', 'get', '/api/v1/admin/invoices'],
            'kasir lihat pelanggan' => ['kasir', 'get', '/api/v1/admin/customers'],
            'sales tutup buku' => ['sales', 'post', '/api/v1/admin/accounting/closing/process'],
            'kasir transfer kas' => ['kasir', 'post', '/api/v1/admin/finance/transfer'],
            'teknisi ubah paket' => ['teknisi', 'post', '/api/v1/admin/packages'],
        ];
    }

    #[DataProvider('forbiddenCases')]
    public function test_staff_roles_are_limited_per_endpoint_group(string $role, string $method, string $uri): void
    {
        Sanctum::actingAs($this->staff($role));

        $this->{$method.'Json'}($uri, [])->assertForbidden();
    }

    public function test_administrator_can_access_system_admin_endpoints(): void
    {
        Sanctum::actingAs($this->staff('administrator'));

        $this->getJson('/api/v1/admin/admin/users')->assertOk();
    }

    public function test_inactive_staff_is_rejected(): void
    {
        Sanctum::actingAs($this->staff('administrator', active: false));

        $this->getJson('/api/v1/admin/admin/users')->assertForbidden();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }
}
