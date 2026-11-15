<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use App\Models\Radius\RadUserGroup;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Role Customer Service: tiket, kontak, lihat tagihan & status; ganti paket hanya lewat pengajuan.
 */
class CsRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $cs;
    private User $admin;
    private Customer $customer;
    private Package $p10;
    private Package $p20;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        $this->cs = User::factory()->create(['role' => 'cs', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->p10 = Package::create(['name' => 'Paket 10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M']);
        $this->p20 = Package::create(['name' => 'Paket 20M', 'type' => 'pppoe', 'price' => 250000, 'upload_speed' => '20M', 'download_speed' => '20M']);
        $this->customer = Customer::create(['customer_code' => 'C-CS', 'username' => 'c-cs', 'name' => 'Pelanggan CS', 'phone' => '0811', 'package_id' => $this->p10->id]);
        $this->customer->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        RadUserGroup::insert(['username' => 'c-cs', 'groupname' => 'Paket 10M', 'priority' => 1]);
    }

    public function test_cs_can_use_customer_service_pages(): void
    {
        $inv = Invoice::create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-CS-1', 'amount' => 150000, 'status' => 'unpaid', 'due_date' => now()]);
        $this->actingAs($this->cs);

        foreach ([route('dashboard'), route('customers.index'), route('customers.show', $this->customer), route('customers.map'),
                  route('tickets.index'), route('tickets.create'), route('invoices.index'), route('invoices.show', $inv),
                  route('online-users.index'), route('auth-logs.index'), route('whatsapp-logs.index'), route('package-requests.index')] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('customers.show', $this->customer))->assertSee('Ubah kontak')->assertSee('Ajukan ganti paket')->assertDontSee('Edit pelanggan');
        $this->get(route('invoices.index'))->assertDontSee('Tandai lunas')->assertDontSee('Generate Massal');

        // Tiket: buat & balas
        $this->post(route('tickets.store'), ['customer_id' => $this->customer->id, 'subject' => 'Internet lambat', 'description' => 'Sejak pagi',
            'type' => 'gangguan', 'priority' => 'medium', 'status' => 'open'])->assertRedirect();
        $this->assertTrue(Ticket::where('subject', 'Internet lambat')->exists());

        // Kontak: hanya HP/email/alamat
        $this->patch(route('customers.contact.update', $this->customer), ['phone' => '0812-555', 'address' => 'Jl. Baru', 'package_id' => $this->p20->id])
            ->assertSessionHas('success');
        $this->assertSame('0812-555', $this->customer->fresh()->phone);
        $this->assertSame($this->p10->id, $this->customer->fresh()->package_id, 'paket tidak bisa diganti lewat ubah kontak');
    }

    public function test_cs_is_refused_outside_its_role(): void
    {
        $inv = Invoice::create(['customer_id' => $this->customer->id, 'invoice_number' => 'INV-CS-2', 'amount' => 150000, 'status' => 'unpaid', 'due_date' => now()]);
        $this->actingAs($this->cs);

        foreach ([route('customers.create'), route('customers.edit', $this->customer), route('customers.activate', $this->customer),
                  route('invoices.create'), route('receivables.index'), route('bank-accounts.index'), route('inventory.index'),
                  route('settings.index'), route('noc.index'), route('users.index'), route('whatsapp-broadcast.create'), route('packages.create')] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->put(route('customers.update', $this->customer), ['name' => 'x'])->assertForbidden();
        $this->delete(route('customers.destroy', $this->customer))->assertForbidden();
        $this->put(route('invoices.update', $inv), ['status' => 'paid'])->assertForbidden();
        $this->post(route('invoices.bulk-mark-paid'), ['ids' => [$inv->id]])->assertForbidden();
        $this->post(route('invoices.write-off', $inv), ['reason' => 'x'])->assertForbidden();
        $this->post(route('radius.disconnect-all'))->assertForbidden();
        $this->post(route('customers.reset-fup', $this->customer))->assertForbidden();
        $this->assertSame('unpaid', $inv->fresh()->status);
    }

    public function test_package_change_goes_through_approval(): void
    {
        $this->actingAs($this->cs)->post(route('package-requests.store', $this->customer), ['to_package_id' => $this->p20->id, 'reason' => 'Minta naik'])
            ->assertSessionHas('success');
        $req = PackageChangeRequest::sole();
        $this->assertSame('pending', $req->status);
        $this->assertSame($this->p10->id, $this->customer->fresh()->package_id, 'belum berubah sebelum disetujui');

        // Pengajuan ganda ditolak; CS tidak bisa menyetujui
        $this->post(route('package-requests.store', $this->customer), ['to_package_id' => $this->p20->id, 'reason' => 'lagi'])->assertSessionHas('error');
        $this->post(route('package-requests.approve', $req))->assertForbidden();
        $this->get(route('customers.show', $this->customer))->assertSee('menunggu persetujuan');

        $this->actingAs($this->admin)->get(route('package-requests.index'))->assertOk()->assertSee('Setujui');
        $this->post(route('package-requests.approve', $req))->assertSessionHas('success');

        $this->assertSame($this->p20->id, $this->customer->fresh()->package_id);
        $this->assertSame('Paket 20M', RadUserGroup::where('username', 'c-cs')->value('groupname'));
        $this->assertSame('approved', $req->fresh()->status);
        $this->assertSame($this->admin->id, $req->fresh()->reviewed_by);

        // Penolakan wajib beralasan
        $req2 = $this->actingAs($this->cs)->post(route('package-requests.store', $this->customer), ['to_package_id' => $this->p10->id, 'reason' => 'Turun'])
            ? PackageChangeRequest::where('status', 'pending')->sole() : null;
        $this->actingAs($this->admin)->post(route('package-requests.reject', $req2), [])->assertSessionHasErrors('note');
        $this->post(route('package-requests.reject', $req2), ['note' => 'Masih kontrak'])->assertSessionHas('success');
        $this->assertSame('rejected', $req2->fresh()->status);
        $this->assertSame($this->p20->id, $this->customer->fresh()->package_id);
    }

    public function test_admin_cannot_approve_own_request_but_administrator_can(): void
    {
        $this->actingAs($this->admin)->post(route('package-requests.store', $this->customer), ['to_package_id' => $this->p20->id, 'reason' => 'x']);
        $req = PackageChangeRequest::sole();
        $this->post(route('package-requests.approve', $req))->assertSessionHas('error');
        $this->assertSame('pending', $req->fresh()->status);

        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]))
            ->post(route('package-requests.approve', $req))->assertSessionHas('success');
    }

    public function test_previously_open_dangerous_routes_are_now_restricted(): void
    {
        foreach (['kasir', 'sales', 'teknisi'] as $role) {
            $u = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($u)->post(route('radius.disconnect-all'))->assertForbidden();
            $this->actingAs($u)->post(route('packages.store'), ['name' => 'Murah', 'price' => 1])->assertForbidden();
            $this->actingAs($u)->delete(route('packages.destroy', $this->p10))->assertForbidden();
        }
        $this->assertNotNull($this->p10->fresh());
        $this->actingAs($this->cs)->get(route('packages.index'))->assertOk();
    }

    public function test_otp_code_is_masked_in_whatsapp_history(): void
    {
        $this->app->instance(WhatsAppService::class, new class extends WhatsAppService {
            public function sendMessage($target, $message, $logId = null): bool
            {
                return true;
            }
        });
        $this->customer->update(['phone' => '081234567890']);
        $this->post('/login-client', ['customer_id' => 'C-CS', 'phone' => '081234567890'])->assertRedirect(route('customer.login.verify'));
        $log = WhatsappLog::sole();
        $this->assertStringContainsString('••••••', $log->message);
        $this->assertDoesNotMatchRegularExpression('/\*\d{6}\*/', $log->message);
    }
}
