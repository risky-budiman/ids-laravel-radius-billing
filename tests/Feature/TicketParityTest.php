<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Web admin dan API admin memakai aturan tiket yang sama (TicketService).
 */
class TicketParityTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();   // WhatsApp grup & pesan pelanggan
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function customer(string $status): Customer
    {
        $c = Customer::create([
            'customer_code' => 'CUST-' . $status . '-' . (++$this->seq), 'username' => 'u_' . $status . '_' . $this->seq, 'name' => 'Pelanggan ' . $status,
            'is_active' => true, 'status' => $status,
        ]);
        Customer::whereKey($c->id)->update(['status' => $status, 'is_active' => true]);

        return $c->fresh();
    }

    private function ticket(string $type, ?Customer $c = null): Ticket
    {
        return Ticket::create([
            'customer_id' => $c?->id, 'type' => $type, 'status' => 'open', 'priority' => 'medium',
            'subject' => "Tiket $type", 'description' => 'uji',
        ]);
    }

    public function test_api_cannot_close_activation_or_dismantle_ticket_outside_the_wizard(): void
    {
        $aktivasi = $this->ticket('aktivasi', $this->customer(Customer::STATUS_WAITING_ACTIVATION));
        $dismantle = $this->ticket('dismantle', $this->customer(Customer::STATUS_WAITING_DISMANTLE));
        Sanctum::actingAs($this->staff('teknisi'));

        $this->postJson("/api/v1/admin/tickets/{$aktivasi->id}/close")->assertStatus(422);
        $this->putJson("/api/v1/admin/tickets/{$dismantle->id}", ['status' => 'resolved'])->assertStatus(422);

        $this->assertSame('open', $aktivasi->fresh()->status);
        $this->assertSame('open', $dismantle->fresh()->status);

        // Tiket gangguan tetap boleh ditutup.
        $gangguan = $this->ticket('gangguan');
        $this->postJson("/api/v1/admin/tickets/{$gangguan->id}/close")->assertOk();
        $this->assertSame('closed', $gangguan->fresh()->status);
    }

    public function test_canceling_a_dismantle_ticket_restores_the_customer_on_web_and_api(): void
    {
        $viaApi = $this->customer(Customer::STATUS_WAITING_DISMANTLE);
        $t1 = $this->ticket('dismantle', $viaApi);
        Sanctum::actingAs($this->staff('admin'));
        $this->putJson("/api/v1/admin/tickets/{$t1->id}", ['status' => 'canceled'])->assertOk();
        $this->assertSame(Customer::STATUS_ACTIVE, $viaApi->fresh()->status);

        $viaWeb = $this->customer(Customer::STATUS_WAITING_DISMANTLE);
        $t2 = $this->ticket('dismantle', $viaWeb);
        $this->actingAs($this->staff('admin'), 'web')->put(route('tickets.update', $t2), [
            'customer_id' => $viaWeb->id, 'type' => 'dismantle', 'status' => 'canceled', 'priority' => 'medium',
            'subject' => 'x', 'description' => 'y',
        ])->assertRedirect(route('tickets.show', $t2));
        $this->assertSame(Customer::STATUS_ACTIVE, $viaWeb->fresh()->status);
    }

    public function test_canceling_an_activation_ticket_does_not_cancel_an_already_active_customer(): void
    {
        $active = $this->customer(Customer::STATUS_ACTIVE);
        $t = $this->ticket('aktivasi', $active);
        Sanctum::actingAs($this->staff('admin'));

        $this->putJson("/api/v1/admin/tickets/{$t->id}", ['status' => 'canceled'])->assertOk();

        $this->assertSame(Customer::STATUS_ACTIVE, $active->fresh()->status);
    }

    public function test_only_administrator_can_delete_tickets_via_api(): void
    {
        $t = $this->ticket('gangguan');

        Sanctum::actingAs($this->staff('teknisi'));
        $this->deleteJson("/api/v1/admin/tickets/{$t->id}")->assertForbidden();
        $this->assertNotNull(Ticket::find($t->id));

        Sanctum::actingAs($this->staff('administrator'));
        $this->deleteJson("/api/v1/admin/tickets/{$t->id}")->assertOk();
        $this->assertNull(Ticket::find($t->id));
    }

    public function test_api_rejects_ticket_types_the_database_cannot_store(): void
    {
        $c = $this->customer(Customer::STATUS_ACTIVE);
        Sanctum::actingAs($this->staff('admin'));

        $this->postJson('/api/v1/admin/tickets', [
            'customer_id' => $c->id, 'type' => 'relokasi', 'priority' => 'low', 'subject' => 'x', 'description' => 'y',
        ])->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_staff_get_exactly_one_notification_per_new_ticket(): void
    {
        Notification::fake();
        $teknisi = $this->staff('teknisi');
        $c = $this->customer(Customer::STATUS_ACTIVE);

        $this->actingAs($this->staff('admin'))->post(route('tickets.store'), [
            'customer_id' => $c->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'high',
            'subject' => 'Internet mati', 'description' => 'LOS merah',
        ])->assertRedirect();

        Notification::assertSentToTimes($teknisi, TicketCreatedNotification::class, 1);
    }
}
