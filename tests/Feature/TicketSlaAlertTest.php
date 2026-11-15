<?php

namespace Tests\Feature;

use App\Jobs\SendCustomWhatsappMessageJob;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Peringatan tiket ke grup WhatsApp teknisi: belum diambil > 2 jam, lewat SLA 24 jam / jadwal.
 */
class TicketSlaAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Setting::updateOrCreate(['key' => 'whatsapp_group_id'], ['value' => '120363000000000000@g.us']);
        Setting::updateOrCreate(['key' => 'whatsapp_group_id_gangguan'], ['value' => 'grup-gangguan@g.us']);
        Cache::forget('app_settings');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function ticketAt(string $created, string $type = 'gangguan', array $attrs = []): Ticket
    {
        $this->travelTo(CarbonImmutable::parse($created));
        $t = Ticket::create(array_merge(['type' => $type, 'status' => 'open', 'priority' => 'high',
            'subject' => 'Internet mati', 'description' => 'LOS'], $attrs));
        $this->travelBack();

        return $t;
    }

    private function sentTo(string $group): array
    {
        return Queue::pushed(SendCustomWhatsappMessageJob::class)
            ->filter(fn ($j) => $j->target === $group)->map(fn ($j) => $j->message)->values()->all();
    }

    public function test_unclaimed_and_late_tickets_are_alerted_once(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00'));
        $c = Customer::create(['customer_code' => 'C-SLA', 'username' => 'csla', 'name' => 'Bu Sari', 'phone' => '0812', 'is_active' => true, 'status' => 'active']);
        $fresh = $this->ticketAt('2026-11-15 11:00', 'gangguan', ['customer_id' => $c->id]);   // 1 jam: belum
        $unclaimed = $this->ticketAt('2026-11-15 09:00', 'gangguan', ['customer_id' => $c->id]); // 3 jam tanpa petugas
        $late = $this->ticketAt('2026-11-14 08:00');                                             // 28 jam
        $done = $this->ticketAt('2026-11-13 08:00');
        $done->update(['status' => 'resolved']);
        $scheduled = $this->ticketAt('2026-11-10 08:00', 'aktivasi', ['due_date' => '2026-11-20']); // terjadwal: belum lewat

        $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00'));
        $this->artisan('app:ticket-sla-alerts')->assertSuccessful();

        $msgs = $this->sentTo('grup-gangguan@g.us');
        $this->assertCount(3, $msgs);   // belum diambil: $unclaimed & $late; lewat SLA: $late
        $this->assertTrue(collect($msgs)->contains(fn ($m) => str_contains($m, 'TIKET BELUM DIAMBIL') && str_contains($m, $unclaimed->ticket_number) && str_contains($m, 'Bu Sari')));
        $this->assertTrue(collect($msgs)->contains(fn ($m) => str_contains($m, 'TIKET LEWAT SLA') && str_contains($m, $late->ticket_number)));
        $this->assertFalse(collect($msgs)->contains(fn ($m) => str_contains($m, $fresh->ticket_number) || str_contains($m, $done->ticket_number)));
        $this->assertSame([], $this->sentTo('120363000000000000@g.us'), 'tiket aktivasi terjadwal belum lewat jadwal');

        // Run berikutnya tidak mengirim ulang
        $this->artisan('app:ticket-sla-alerts')->assertSuccessful();
        $this->assertCount(3, $this->sentTo('grup-gangguan@g.us'));
    }

    public function test_scheduled_ticket_alerts_after_its_due_date_and_reopened_ticket_alerts_again(): void
    {
        $scheduled = $this->ticketAt('2026-11-01 08:00', 'aktivasi', ['due_date' => '2026-11-14', 'assigned_to' => null]);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00'));
        $this->artisan('app:ticket-sla-alerts')->assertSuccessful();
        $this->assertTrue(collect($this->sentTo('120363000000000000@g.us'))->contains(fn ($m) => str_contains($m, 'LEWAT SLA') && str_contains($m, $scheduled->ticket_number)));

        $late = $this->ticketAt('2026-11-10 08:00');
        $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00'));
        $this->artisan('app:ticket-sla-alerts');
        $late->update(['status' => 'resolved']);
        $late->update(['status' => 'open']);   // dibuka ulang
        $this->assertNull($late->fresh()->sla_alerted_at);
        $before = count($this->sentTo('grup-gangguan@g.us'));
        $this->artisan('app:ticket-sla-alerts');
        $this->assertGreaterThan($before, count($this->sentTo('grup-gangguan@g.us')));
    }

    public function test_alerts_are_scheduled_every_15_minutes(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($e) => str_contains($e->command ?? '', 'app:ticket-sla-alerts') && $e->expression === '*/15 * * * *'));
    }
}
