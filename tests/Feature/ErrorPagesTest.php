<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_is_indonesian_and_self_contained(): void
    {
        $res = $this->get('/halaman-yang-tidak-ada');

        $res->assertNotFound()->assertSee('Halaman tidak ditemukan');
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $res->getContent());
    }

    public function test_database_error_on_page_load_shows_500_instead_of_redirect_loop(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->get('/__uji-db-error', fn () => \Illuminate\Support\Facades\DB::select('select fungsi_tidak_ada()'));

        $this->get('/__uji-db-error')
            ->assertStatus(500)
            ->assertSee('Terjadi kesalahan pada server');
    }

    public function test_database_error_on_form_submit_returns_back_with_message(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->post('/__uji-db-error', fn () => \Illuminate\Support\Facades\DB::select('select fungsi_tidak_ada()'));

        $this->from('/asal')->post('/__uji-db-error')
            ->assertRedirect('/asal')
            ->assertSessionHas('error');
    }

    public function test_403_for_staff_without_permission_links_to_admin_dashboard(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);

        $this->actingAs($kasir)->get('/admin/users')
            ->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee(route('dashboard'), false);
    }

    public function test_403_for_guest_on_portal_area_does_not_point_to_admin_dashboard(): void
    {
        $customer = \App\Models\Customer::create([
            'customer_code' => 'CUST-ERR-01', 'username' => 'cust_err_01', 'name' => 'Pelanggan Error',
            'is_active' => true, 'status' => \App\Models\Customer::STATUS_ACTIVE,
        ]);
        $invoice = \App\Models\Invoice::create([
            'customer_id' => $customer->id, 'invoice_number' => 'INV-ERR-001', 'amount' => 100000,
            'status' => 'unpaid', 'due_date' => now()->addDays(7),
        ]);

        // Portal pembayaran dengan tautan bertanda tangan palsu -> 403 (signed middleware)
        $res = $this->get('/portal/invoice/' . $invoice->id . '/pay?gateway=midtrans&signature=palsu');

        $res->assertForbidden();
        $this->assertStringNotContainsString(route('dashboard'), $res->getContent());
    }
}
