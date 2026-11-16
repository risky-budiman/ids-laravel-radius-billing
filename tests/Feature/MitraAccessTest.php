<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akun mitra bisa masuk dan membuka halamannya sendiri (dulu terblokir grup staf), tanpa akses ke data staf.
 */
class MitraAccessTest extends TestCase
{
    use RefreshDatabase;

    private function mitra(): User
    {
        return User::factory()->create(['role' => 'mitra', 'is_active' => true, 'password' => bcrypt('rahasia123'),
            'commission_rate' => 10, 'commission_type' => 'percentage']);
    }

    public function test_mitra_login_lands_on_own_partner_page(): void
    {
        $m = $this->mitra();

        $this->post(route('login'), ['login' => $m->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('partners.show', $m));

        $this->get(route('partners.show', $m))->assertOk()->assertSee($m->name);
        $this->get(route('dashboard'))->assertRedirect(route('partners.show', $m));
        $this->get(route('partners.withdrawals'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_mitra_cannot_see_other_partners_or_staff_pages(): void
    {
        $m = $this->mitra();
        $other = $this->mitra();
        $this->actingAs($m);

        $this->get(route('partners.show', $other))->assertForbidden();
        $this->get(route('partners.index'))->assertForbidden();
        $this->get(route('customers.index'))->assertForbidden();
        $this->get(route('invoices.index'))->assertForbidden();
    }

    public function test_staff_still_reach_partner_management_and_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('partners.index'))->assertOk();
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('profile.edit'))->assertOk();
        $this->actingAs($teknisi)->get(route('partners.index'))->assertForbidden();
    }
}
