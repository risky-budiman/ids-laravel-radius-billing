<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Login portal pelanggan dengan OTP WhatsApp.
 */
class PortalOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private object $wa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create(['customer_code' => '001001000777', 'username' => 'budi', 'name' => 'Budi',
            'phone' => '+62 812-3456-7890', 'status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        $this->wa = new class extends WhatsAppService {
            public array $sent = [];
            public bool $ok = true;

            public function sendMessage($target, $message, $logId = null): bool
            {
                $this->sent[] = [$target, $message];

                return $this->ok;
            }
        };
        $this->app->instance(WhatsAppService::class, $this->wa);
    }

    private function lastCode(): string
    {
        preg_match('/\*(\d{6})\*/', end($this->wa->sent)[1], $m);

        return $m[1];
    }

    private function setting(string $value): void
    {
        Setting::updateOrCreate(['key' => 'portal_login_otp'], ['value' => $value, 'type' => 'string']);
        Cache::forget('app_settings');
    }

    public function test_login_requires_whatsapp_code_sent_to_registered_number(): void
    {
        $this->post('/login-client', ['customer_id' => '001001000777', 'phone' => '081234567890'])
            ->assertRedirect(route('customer.login.verify'));
        $this->assertGuest('customer');
        $this->assertSame('+62 812-3456-7890', $this->wa->sent[0][0], 'dikirim ke nomor terdaftar');

        $this->get(route('customer.login.verify'))->assertOk()->assertSee('890');

        $wrong = $this->lastCode() === '000000' ? '111111' : '000000';
        $this->post(route('customer.login.verify.post'), ['code' => $wrong])->assertSessionHas('error');
        $this->assertGuest('customer');

        $this->post(route('customer.login.verify.post'), ['code' => $this->lastCode()])->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($this->customer->fresh(), 'customer');
    }

    public function test_code_expires_after_too_many_wrong_attempts_and_resend_is_throttled(): void
    {
        $this->post('/login-client', ['customer_id' => 'budi', 'phone' => '0812-3456-7890']);
        $good = $this->lastCode();
        $wrong = $good === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.login.verify.post'), ['code' => $wrong]);
        }
        $this->post(route('customer.login.verify.post'), ['code' => $good])->assertSessionHas('error');
        $this->assertGuest('customer');

        // Kirim ulang < 60 detik ditolak
        $this->post(route('customer.login.resend'))->assertSessionHas('error');
        $this->assertCount(1, $this->wa->sent);
        $this->travel(61)->seconds();
        $this->post(route('customer.login.resend'))->assertSessionHas('success');
        $this->assertCount(2, $this->wa->sent);
    }

    public function test_failed_whatsapp_send_does_not_log_in_and_database_id_is_not_accepted(): void
    {
        $this->wa->ok = false;
        $this->post('/login-client', ['customer_id' => '001001000777', 'phone' => '081234567890'])->assertSessionHas('error');
        $this->assertGuest('customer');

        // Dulu id database (berurutan) diterima sebagai ID pelanggan
        $this->wa->ok = true;
        $this->post('/login-client', ['customer_id' => (string) $this->customer->id, 'phone' => '081234567890'])->assertSessionHas('error');
        $this->assertCount(1, $this->wa->sent);
    }

    public function test_otp_can_be_disabled(): void
    {
        $this->setting('0');
        $this->post('/login-client', ['customer_id' => '001001000777', 'phone' => '081234567890'])->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($this->customer->fresh(), 'customer');
        $this->assertCount(0, $this->wa->sent);
    }
}
