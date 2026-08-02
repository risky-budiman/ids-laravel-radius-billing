<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPerformanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-TKT-001',
            'username' => 'test_customer_tkt',
            'name' => 'Ticket Test Customer',
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function test_ticket_index_displays_performance_recap_correctly()
    {
        $this->actingAs($this->user);

        // 1. Create Aktivasi ticket in waiting status (open)
        Ticket::create([
            'customer_id' => $this->customer->id,
            'type' => 'aktivasi',
            'status' => 'open',
            'priority' => 'medium',
            'subject' => 'Aktivasi waiting test',
            'description' => 'Aktivasi desc',
        ]);

        // 2. Create Aktivasi ticket in processed status (resolved)
        Ticket::create([
            'customer_id' => $this->customer->id,
            'type' => 'aktivasi',
            'status' => 'resolved',
            'priority' => 'high',
            'subject' => 'Aktivasi processed test',
            'description' => 'Aktivasi desc',
        ]);

        // 3. Create Gangguan ticket in processed status (closed)
        Ticket::create([
            'customer_id' => $this->customer->id,
            'type' => 'gangguan',
            'status' => 'closed',
            'priority' => 'urgent',
            'subject' => 'Gangguan closed test',
            'description' => 'Gangguan desc',
        ]);

        $response = $this->get(route('tickets.index'));
        $response->assertStatus(200);

        // Verification of stats content:
        // Aktivasi total: 2, processed: 1, waiting: 1 (50% Done)
        // Gangguan total: 1, processed: 1, waiting: 0 (100% Done)
        // Dismantle total: 0, processed: 0, waiting: 0 (0% Done)
        // Overall: 3 total, 2 processed, 1 waiting (67% Done)
        
        $response->assertSee('Aktivasi Layanan');
        $response->assertSee('Gangguan Layanan');
        $response->assertSee('Dismantle Layanan');
        $response->assertSee('Rekap Kinerja Tim & Status Tiket', false);

        // Check exact numbers or percentages appear in view
        $response->assertSee('50% Done');
        $response->assertSee('100% Done');
        $response->assertSee('67%'); // Overall percentage

        // Verify detailed statuses labels are present
        $response->assertSee('Selesai');
        $response->assertSee('Baru (Open)');
        $response->assertSee('Proses');
        $response->assertSee('Batal');
    }
}
