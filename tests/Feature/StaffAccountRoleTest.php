<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Akun staf: edit administrator tidak menurunkannya, administrator terakhir terlindungi, API memakai role yang ada.
 */
class StaffAccountRoleTest extends TestCase
{
    use RefreshDatabase;

    private function form(User $u, array $over = []): array
    {
        return array_merge(['name' => $u->name, 'email' => $u->email, 'role' => $u->role], $over);
    }

    public function test_editing_another_administrator_keeps_the_role(): void
    {
        $me = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($me)->get(route('users.edit', $other))->assertOk()->assertSee('value="administrator"', false);
        $this->actingAs($me)->put(route('users.update', $other), $this->form($other, ['name' => 'Nama Baru']))->assertRedirect(route('users.index'));

        $other->refresh();
        $this->assertSame('Nama Baru', $other->name);
        $this->assertSame('administrator', $other->role, 'dulu turun menjadi admin');
    }

    public function test_own_account_can_be_saved_and_own_role_cannot_change(): void
    {
        $me = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        // dulu gagal validasi: role tersembunyi 'administrator' tidak ada di daftar
        $this->actingAs($me)->put(route('users.update', $me), $this->form($me, ['name' => 'Saya', 'role' => 'kasir']))
            ->assertRedirect(route('users.index'))->assertSessionHasNoErrors();
        $this->assertSame('administrator', $me->fresh()->role);
        $this->assertSame('Saya', $me->fresh()->name);
    }

    public function test_last_active_administrator_is_protected(): void
    {
        $me = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->assertFalse($me->isLastActiveAdministrator());

        // Menurunkan administrator lain boleh selama masih ada administrator aktif
        $this->actingAs($me)->put(route('users.update', $other), $this->form($other, ['role' => 'admin']))->assertRedirect(route('users.index'));
        $this->assertSame('admin', $other->fresh()->role);
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'user_role_changed')->exists());

        User::factory()->create(['role' => 'administrator', 'is_active' => false]);
        $this->assertTrue($me->fresh()->isLastActiveAdministrator(), 'administrator nonaktif tidak dihitung');

        // API: tidak bisa menonaktifkan akun sendiri (administrator terakhir)
        Sanctum::actingAs($me);
        $this->postJson("/api/v1/admin/admin/users/{$me->id}/toggle-status")->assertStatus(422);
        $this->assertTrue((bool) $me->fresh()->is_active);
    }

    public function test_account_with_history_cannot_be_deleted_but_unused_account_can(): void
    {
        $me = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $used = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        \App\Models\ActivityLog::create(['user_id' => $used->id, 'action' => 'login', 'description' => 'masuk']);
        $unused = User::factory()->create(['role' => 'kasir', 'is_active' => true]);

        $this->actingAs($me)->delete(route('users.destroy', $used))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($used->fresh(), 'dulu terhapus permanen beserta riwayatnya');
        $this->assertSame(1, \App\Models\ActivityLog::where('user_id', $used->id)->count());

        $this->actingAs($me)->delete(route('users.destroy', $unused))->assertRedirect(route('users.index'));
        $this->assertNull(User::find($unused->id));
    }

    public function test_sales_flag_is_visible_and_ignored_for_partners(): void
    {
        $me = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true, 'is_sales' => true]);
        $this->actingAs($me)->get(route('users.index'))->assertOk()->assertSee('+ Sales');
        $this->assertTrue($kasir->hasRole('sales'));

        // Mitra dengan penanda lama tidak mendapat akses sales
        $mitra = User::factory()->create(['role' => 'mitra', 'is_active' => true, 'is_sales' => true, 'commission_type' => 'percentage']);
        $this->assertFalse($mitra->hasRole('sales'));
        $this->assertFalse($mitra->isSales());

        // Form: penanda tidak disimpan untuk mitra
        $this->actingAs($me)->put(route('users.update', $mitra), ['name' => $mitra->name, 'email' => $mitra->email, 'role' => 'mitra', 'is_sales' => '1'])
            ->assertRedirect(route('users.index'));
        $this->assertFalse((bool) $mitra->fresh()->is_sales);
    }

    public function test_api_store_user_uses_real_roles_and_min_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));

        $this->postJson('/api/v1/admin/admin/users', ['name' => 'X', 'email' => 'x@a.id', 'password' => 'rahasia123', 'role' => 'finance'])
            ->assertStatus(422)->assertJsonValidationErrors('role');
        $this->postJson('/api/v1/admin/admin/users', ['name' => 'X', 'email' => 'x@a.id', 'password' => '123456', 'role' => 'cs'])
            ->assertStatus(422)->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/admin/admin/users', ['name' => 'X', 'email' => 'x@a.id', 'password' => 'rahasia123', 'role' => 'cs'])
            ->assertCreated();
        $this->assertSame('cs', User::where('email', 'x@a.id')->value('role'));
    }
}
