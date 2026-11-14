<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Beranda (/) mengarahkan pengunjung sesuai status login. */
    public function test_home_redirects_guests_to_customer_login(): void
    {
        $this->get('/')->assertRedirect(route('customer.login'));
    }

    public function test_home_redirects_staff_to_admin_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($staff)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_customer_login_page_renders(): void
    {
        $this->get(route('customer.login'))->assertOk()->assertSee('Masuk ke Portal Pelanggan');
    }
}
