<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman Kelola Staf: hanya staf, filter & pencarian, ringkasan, aktivitas dari sesi & log login. */
class StaffListTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_staff_only_with_filters_and_real_activity(): void
    {
        $root = User::factory()->create(['role' => 'administrator', 'is_active' => true, 'name' => 'Root Admin']);
        $budi = User::factory()->create(['role' => 'teknisi', 'is_active' => true, 'name' => 'Budi Teknisi', 'email' => 'budi@kantor.id']);
        User::factory()->create(['role' => 'kasir', 'is_active' => false, 'name' => 'Kasir Lama']);
        User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Pelanggan Portal']);
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert(['user_id' => $budi->id, 'action' => 'login', 'description' => 'login', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);

        $res = $this->actingAs($root)->get(route('users.index'))->assertOk()
            ->assertSee('Kelola Staf')->assertSee('Budi Teknisi')->assertSee('Kasir Lama')
            ->assertDontSee('Pelanggan Portal')                         // akun portal pelanggan bukan staf
            ->assertSee('Masuk terakhir')->assertSee('Belum pernah masuk')
            ->assertDontSee('ui-avatars.com');                          // nama staf tidak dikirim ke layanan luar
        $this->assertSame(['total' => 3, 'active' => 2, 'online' => 0, 'two_factor' => 0], $res->viewData('stats'));
        $this->assertSame(now()->subDays(2)->toDateString(), \Illuminate\Support\Carbon::parse(
            $res->viewData('users')->firstWhere('id', $budi->id)->last_login_at)->toDateString());

        $this->get(route('users.index', ['role' => 'teknisi']))->assertSee('Budi Teknisi')->assertDontSee('Kasir Lama');
        $this->get(route('users.index', ['status' => 'inactive']))->assertSee('Kasir Lama')->assertDontSee('Budi Teknisi');
        $this->get(route('users.index', ['q' => 'budi@kantor']))->assertSee('Budi Teknisi')->assertDontSee('Kasir Lama');
        $this->get(route('users.index', ['q' => 'tidak-ada']))->assertSee('Tidak ada staf yang cocok dengan filter.');
    }
}
