<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RouteAccess;
use App\View\SidebarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Matriks akses: middleware role pada rute adalah sumber tunggal; sidebar, tombol (@canroute) dan halaman
 * "Hak akses per role" mengikutinya.
 */
class AccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Rute admin yang memang terbuka tanpa role (sebelum/sesudah login). */
    private const OPEN = ['login', 'logout', 'password.request', 'password.email', 'password.reset', 'password.store',
        'verification.notice', 'verification.verify', 'verification.send', 'password.confirm', 'password.update',
        'two-factor.challenge', 'two-factor.verify'];   // kode 2FA setelah password benar, sebelum login

    public function test_every_staff_route_is_restricted_by_role(): void
    {
        $open = [];
        foreach (Route::getRoutes() as $r) {
            if ($r->getName() && str_starts_with($r->uri(), 'admin') && ! in_array($r->getName(), self::OPEN, true) && ! RouteAccess::listsOf($r)) {
                $open[] = $r->getName();
            }
        }
        $this->assertSame([], $open, 'Rute admin tanpa middleware role: ' . implode(', ', $open));
    }

    public function test_sidebar_only_shows_pages_the_role_may_open(): void
    {
        foreach (array_keys(User::STAFF_ROLES) as $role) {
            $u = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($u);
            foreach (SidebarMenu::admin($u) as $section) {
                foreach ($section['items'] as $item) {
                    foreach ($item['type'] === 'group' ? $item['children'] : [$item] as $link) {
                        $ok = RouteAccess::allows($u, $link['route']) || ($role === 'mitra' && $link['route'] === 'partners.show');
                        $this->assertTrue($ok, "{$role} melihat menu {$link['label']} ({$link['route']}) yang akan ditolak");
                    }
                }
            }
        }

        // Kasus nyata yang dulu salah: Desain Invoice tampil untuk admin & kasir padahal khusus administrator
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee(route('invoice-templates.index'));
    }

    public function test_buttons_follow_route_access(): void
    {
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('packages.index'))->assertOk()->assertDontSee('Paket Baru');

        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($administrator)->get(route('packages.index'))->assertOk()->assertSee('Paket Baru');

        $cs = User::factory()->create(['role' => 'cs', 'is_active' => true]);
        $this->actingAs($cs)->get(route('customers.index'))->assertOk()->assertDontSee('Aksi Massal');

        // Admin melihat Bagan Akun tanpa tombol tambah (khusus administrator) dan halaman tetap utuh
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('accounting.coa.index'))->assertOk()->assertDontSee('Tambah Akun Baru');
    }

    public function test_access_matrix_page_for_administrator_only(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($administrator)->get(route('access.matrix'))->assertOk()
            ->assertSee('Hak Akses per Role')->assertSee('Pelanggan')->assertSee('Customer Service (CS)')
            ->assertSee('Penuh')->assertSee('customers.destroy');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('access.matrix'))->assertForbidden();
    }

    public function test_administrator_can_grant_and_revoke_access_per_feature(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);

        // Bawaan: teknisi tidak boleh menambah paket
        $this->actingAs($teknisi)->get(route('packages.create'))->assertForbidden();

        $this->actingAs($administrator)->postJson(route('access.matrix.update'), ['role' => 'teknisi', 'feature' => 'packages', 'level' => 'full'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'role_permissions_updated')->exists());

        \App\Support\RouteAccess::flush();
        $this->actingAs($teknisi)->get(route('packages.create'))->assertOk();
        $this->actingAs($teknisi)->get(route('packages.index'))->assertOk()->assertSee('Paket Baru');   // tombol ikut

        // "Lihat saja": halaman daftar boleh, aksi ubah tidak
        $this->actingAs($administrator)->postJson(route('access.matrix.update'), ['role' => 'teknisi', 'feature' => 'packages', 'level' => 'view'])->assertOk();
        \App\Support\RouteAccess::flush();
        $this->actingAs($teknisi)->get(route('packages.index'))->assertOk()->assertDontSee('Paket Baru');
        $this->actingAs($teknisi)->get(route('packages.create'))->assertForbidden();   // form tambah = aksi ubah
        $this->actingAs($teknisi)->post(route('packages.store'), [])->assertForbidden();

        // Kembali ke bawaan
        $this->actingAs($administrator)->post(route('access.matrix.reset'), ['role' => 'teknisi'])->assertSessionHas('success');
        \App\Support\RouteAccess::flush();
        $this->actingAs($teknisi)->get(route('packages.create'))->assertForbidden();
        $this->assertSame(0, \App\Models\RolePermission::count());
    }

    public function test_revoking_hides_menu_and_blocks_route(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);
        $this->actingAs($kasir)->get(route('invoices.index'))->assertOk();

        $this->actingAs($administrator)->postJson(route('access.matrix.update'), ['role' => 'kasir', 'route' => 'invoices.index', 'value' => 'deny'])->assertOk();
        \App\Support\RouteAccess::flush();

        $this->actingAs($kasir)->get(route('invoices.index'))->assertForbidden();
        $menu = collect(SidebarMenu::admin($kasir))->flatMap(fn ($s) => collect($s['items'])->flatMap(fn ($i) => $i['type'] === 'group' ? $i['children'] : [$i]))->pluck('route');
        $this->assertNotContains('invoices.index', $menu->all());

        $this->actingAs($administrator)->get(route('access.matrix'))->assertOk()->assertSee('Kembalikan semua ke bawaan');
    }

    public function test_safeguards_administrator_role_and_locked_features(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Role administrator tidak bisa dibatasi
        $this->actingAs($administrator)->postJson(route('access.matrix.update'), ['role' => 'administrator', 'feature' => 'customers', 'level' => 'none'])
            ->assertStatus(422);
        // Fitur terkunci (Kelola Staf) tidak bisa diberikan
        $this->actingAs($administrator)->postJson(route('access.matrix.update'), ['role' => 'admin', 'route' => 'users.index', 'value' => 'allow'])->assertOk();
        \App\Support\RouteAccess::flush();
        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
        $this->assertSame(0, \App\Models\RolePermission::count());

        // Hanya administrator yang boleh mengubah
        $this->actingAs($admin)->postJson(route('access.matrix.update'), ['role' => 'kasir', 'feature' => 'invoices', 'level' => 'none'])->assertForbidden();
    }

    public function test_route_access_mirrors_role_middleware_including_sales_flag(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);
        $this->assertFalse(RouteAccess::allows($kasir, 'users.index'));
        $this->assertFalse(RouteAccess::allows($kasir, 'route.yang.tidak.ada'));
        $this->assertFalse(RouteAccess::allows(null, 'dashboard'));

        // Penanda "juga sales" membuka rute sales, seperti RoleMiddleware
        $salesRoute = collect(Route::getRoutes()->getRoutes())->first(function ($r) {
            $lists = RouteAccess::listsOf($r);

            return $r->getName() && $lists
                && collect($lists)->every(fn ($l) => in_array('sales', $l, true))
                && collect($lists)->contains(fn ($l) => ! in_array('kasir', $l, true));
        });
        $this->assertNotNull($salesRoute);
        $this->assertFalse(RouteAccess::allows($kasir, $salesRoute->getName()));
        $kasir->forceFill(['is_sales' => true])->save();
        $this->assertTrue(RouteAccess::allows($kasir->fresh(), $salesRoute->getName()));
    }
}
