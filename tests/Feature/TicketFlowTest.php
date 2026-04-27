<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create basic roles with is_active = true
        $this->adminUser = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
    }

    /**
     * Test 8.1: Activation & Dismantle tickets should NOT be closed manually via TicketController
     */
    public function test_activation_and_dismantle_tickets_cannot_be_closed_manually()
    {
        $types = ['aktivasi', 'dismantle'];

        foreach ($types as $type) {
            $customer = Customer::factory()->create(['status' => Customer::STATUS_WAITING_ACTIVATION]);
            $ticket = Ticket::create([
                'customer_id' => $customer->id,
                'type' => $type,
                'status' => 'open',
                'subject' => "New $type",
                'description' => "Process $type",
                'priority' => 'medium'
            ]);

            $response = $this->actingAs($this->adminUser)
                ->from(route('tickets.edit', $ticket))
                ->put(route('tickets.update', $ticket), [
                    'customer_id' => $customer->id,
                    'type' => $type,
                    'status' => 'closed', // Attempting to close manually
                    'priority' => 'medium',
                    'subject' => "New $type Updated",
                    'description' => "Process $type",
                ]);

            $response->assertRedirect(route('tickets.edit', $ticket));
            $response->assertSessionHas('error');
            
            $ticket->refresh();
            $this->assertNotEquals('closed', $ticket->status);
        }
    }

    /**
     * Test 8.2: Notifications should only go to targeted roles
     */
    public function test_notifications_are_targeted_to_relevant_roles()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $kasir = User::factory()->create(['role' => 'kasir', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)->post(route('tickets.store'), [
            'type' => 'gangguan',
            'status' => 'open',
            'priority' => 'high',
            'subject' => 'Internet Down',
            'description' => 'Customer reports internet is down',
            'customer_id' => null,
            'assigned_to' => null
        ]);

        $response->assertRedirect();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $admin,
            \App\Notifications\TicketCreatedNotification::class
        );
        
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $teknisi,
            \App\Notifications\TicketCreatedNotification::class
        );

        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $kasir,
            \App\Notifications\TicketCreatedNotification::class
        );
    }

    /**
     * Test 8.3: Ticket numbers should use professional prefixes and format
     */
    public function test_ticket_number_format_and_prefixes()
    {
        $types = [
            'gangguan' => 'TT',
            'aktivasi' => 'AO',
            'dismantle' => 'DO'
        ];

        foreach ($types as $type => $expectedPrefix) {
            $ticket = Ticket::create([
                'type' => $type,
                'status' => 'open',
                'priority' => 'medium',
                'subject' => "Test $type",
                'description' => 'Test'
            ]);

            // Format: PREFIX/YYYYMMDD/XXXX
            $this->assertStringStartsWith($expectedPrefix . '/' . now()->format('Ymd'), $ticket->ticket_number);
            $this->assertMatchesRegularExpression('/^[A-Z]+\/\d{8}\/\d{4}$/', $ticket->ticket_number);
        }
    }
}
