<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Memastikan layout admin (sidebar, navbar, footer) ter-render untuk setiap role
 * dan menu yang tampil sesuai hak akses route-nya.
 */
class AppShellRenderTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [
            'administrator' => ['administrator', ['Manage Staff', 'Chart of Accounts', 'Backup &amp; Restore'], []],
            'admin' => ['admin', ['Subscribers', 'Invoices', 'Chart of Accounts'], ['Manage Staff', 'Backup &amp; Restore']],
            'teknisi' => ['teknisi', ['OLT Master', 'Online Users', 'Warehouse'], ['Invoices', 'Chart of Accounts']],
            'kasir' => ['kasir', ['Invoices', 'Kas &amp; Bank'], ['Chart of Accounts', 'Subscribers', 'Laporan Keuangan']],
            'sales' => ['sales', ['Sales Dashboard', 'Subscribers', 'Tickets'], ['Invoices', 'Manage Staff']],
        ];
    }

    #[DataProvider('roles')]
    public function test_app_shell_renders_with_role_based_menu(string $role, array $visible, array $hidden): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('changelog.index'));

        $response->assertOk();
        $response->assertSee('aria-label="Navigasi utama"', false);
        $response->assertSee(route('api.search'), false);

        foreach ($visible as $label) {
            $response->assertSee($label, false);
        }
        foreach ($hidden as $label) {
            $response->assertDontSee('>'.$label.'<', false);
        }
    }

    public function test_company_settings_link_only_for_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('changelog.index'))
            ->assertOk()
            ->assertDontSee(route('settings.company'), false);

        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($administrator)->get(route('changelog.index'))
            ->assertOk()
            ->assertSee(route('settings.company'), false);
    }
}
