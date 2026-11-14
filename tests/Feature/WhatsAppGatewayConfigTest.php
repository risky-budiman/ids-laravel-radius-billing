<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gateway WA self-hosted dibaca dari config (services.wa_gateway), bukan env(): setelah
 * `php artisan config:cache` env() di luar config/ kosong sehingga kunci dari .env diabaikan.
 */
class WhatsAppGatewayConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_hosted_gateway_uses_configured_url_and_key(): void
    {
        config(['services.wa_gateway.url' => 'http://wa.internal:3100/api', 'services.wa_gateway.key' => 'kunci-produksi']);
        Gateway::create(['type' => 'whatsapp', 'provider' => 'self_hosted', 'is_active' => true, 'credentials' => []]);
        Http::fake(['wa.internal:3100/*' => Http::response(['ok' => true])]);

        $this->assertTrue(app(WhatsAppService::class)->sendMessage('6281200000000', 'Tes'));

        Http::assertSent(fn (Request $r) => $r->url() === 'http://wa.internal:3100/api/send'
            && $r->header('X-API-Key') === ['kunci-produksi']);
    }
}
