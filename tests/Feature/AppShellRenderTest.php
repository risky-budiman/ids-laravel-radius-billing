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
            'administrator' => ['administrator', ['Kelola Staf', 'Bagan Akun (COA)', 'Backup &amp; Restore'], []],
            'admin' => ['admin', ['Pelanggan', 'Desain Invoice', 'Bagan Akun (COA)'], ['Kelola Staf', 'Backup &amp; Restore']],
            'teknisi' => ['teknisi', ['Data OLT', 'Pengguna Online', 'Gudang'], ['Desain Invoice', 'Bagan Akun (COA)']],
            'kasir' => ['kasir', ['Desain Invoice', 'Kas &amp; Bank'], ['Bagan Akun (COA)', 'Pelanggan', 'Laporan Keuangan']],
            'sales' => ['sales', ['Dashboard Sales', 'Pelanggan', 'Tiket'], ['Desain Invoice', 'Kelola Staf']],
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

    public static function dashboardRoles(): array
    {
        return [
            // role, harus terlihat, tidak boleh terlihat
            'administrator' => ['administrator', ['Laba Rugi Bulan Ini', 'Generate tagihan', 'Semua log'], []],
            'admin' => ['admin', ['Laba Rugi Bulan Ini', 'Generate tagihan'], ['Semua log']],
            'kasir' => ['kasir', ['Generate tagihan', 'Buat invoice'], ['Laba Rugi Bulan Ini', 'Tambah pelanggan', 'Tiket &amp; gangguan']],
            'teknisi' => ['teknisi', ['Tambah pelanggan', 'Pengguna online'], ['Generate tagihan', 'Laba Rugi Bulan Ini']],
            'sales' => ['sales', ['Tambah pelanggan'], ['Generate tagihan', 'Pengguna online', 'Laba Rugi Bulan Ini']],
        ];
    }

    #[DataProvider('dashboardRoles')]
    public function test_dashboard_renders_with_role_based_actions(string $role, array $visible, array $hidden): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        foreach ($visible as $text) {
            $response->assertSee($text, false);
        }
        foreach ($hidden as $text) {
            $response->assertDontSee($text, false);
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
