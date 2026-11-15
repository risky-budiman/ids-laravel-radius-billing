<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pengaturan Aplikasi: hanya administrator, hanya kunci yang dikenal.
 */
class AppSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $extra = []): array
    {
        return array_merge(['company_name' => 'PT Uji', 'company_email' => 'a@b.id', 'commission_type' => 'percentage',
            'default_sales_commission_type' => 'fixed', 'enable_fup_module' => '1'], $extra);
    }

    public function test_non_administrator_staff_cannot_open_or_change_app_settings(): void
    {
        foreach (['kasir', 'teknisi', 'sales', 'admin'] as $role) {
            $u = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($u)->get(route('settings.index'))->assertForbidden();
            $this->actingAs($u)->post(route('settings.update'), $this->form(['portal_login_otp' => '0']))->assertForbidden();
        }
        $this->assertNull(Setting::where('key', 'portal_login_otp')->value('value'));
    }

    public function test_administrator_saves_only_known_keys(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->get(route('settings.index'))->assertOk()->assertSee('Identitas perusahaan')->assertSee('Modul tambahan');

        $this->post(route('settings.update'), $this->form([
            'portal_login_otp' => '0', 'isolir_enabled' => '1',   // kunci lain diabaikan
            'company_logo' => UploadedFile::fake()->image('logo.png'),
        ]))->assertRedirect(route('settings.index'))->assertSessionHas('success');

        $this->assertSame('PT Uji', get_setting('company_name'));
        $this->assertSame('1', Setting::where('key', 'enable_fup_module')->value('value'));
        $this->assertSame('0', Setting::where('key', 'enable_partner_module')->value('value'), 'kotak tidak dicentang = 0');
        $this->assertNull(Setting::where('key', 'portal_login_otp')->value('value'));
        $this->assertNull(Setting::where('key', 'isolir_enabled')->value('value'));
        Storage::disk('public')->assertExists(Setting::where('key', 'company_logo')->value('value'));

        $this->post(route('settings.update'), $this->form(['company_website' => 'bukan url']))->assertSessionHasErrors('company_website');
    }

    public function test_invoice_and_portal_page_and_api_whitelist(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->actingAs($admin)->get(route('settings.company'))->assertOk()->assertSee('Invoice & Portal')->assertDontSee('Visual Identity');
        $this->post(route('settings.company.update'), ['invoice_format' => 'Thermal', 'portal_login_otp' => '0'])->assertSessionHas('success');
        $this->assertSame('Thermal', get_setting('invoice_format'));
        $this->assertSame('0', get_setting('portal_login_otp'));

        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/admin/admin/settings', ['company_name' => 'PT API', 'portal_login_otp' => '1', 'radius_auto_coa' => false])->assertOk();
        $this->assertSame('PT API', get_setting('company_name'), 'cache dibersihkan');
        $this->assertSame('0', get_setting('portal_login_otp'), 'kunci di luar daftar diabaikan');
        $this->assertSame('0', Setting::where('key', 'radius_auto_coa')->value('value'));
    }
}
