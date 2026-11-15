<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Angka keuangan & log aktivitas di dasbor hanya untuk role yang berhak. */
class DashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_and_activity_hidden_for_operational_roles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]))
            ->get(route('dashboard'))->assertOk()->assertDontSee('Aktivitas Terbaru')->assertDontSee('Kas Terkumpul');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->get(route('dashboard'))->assertOk()->assertSee('Aktivitas Terbaru');
    }

    public function test_api_dashboard_hides_money_from_non_billing_roles(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'teknisi', 'is_active' => true]));
        $this->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('revenue', null)->assertJsonPath('recent_activities', []);

        Sanctum::actingAs(User::factory()->create(['role' => 'kasir', 'is_active' => true]));
        $this->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonStructure(['revenue' => ['this_month']]);
    }
}
