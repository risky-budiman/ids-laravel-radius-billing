<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsappGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_it_sends_free_form_text_message_successfully()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messaging_product' => 'whatsapp', 'messages' => [['id' => 'wamid.HBgM...']]], 200)
        ]);

        $gateway = Gateway::create([
            'provider' => 'meta',
            'type' => 'whatsapp',
            'is_active' => true,
            'credentials' => [
                'phone_number_id' => '1234567890',
                'token' => 'fake-access-token',
            ]
        ]);

        $service = new WhatsAppService();
        $result = $service->sendMessage('08123456789', 'Hello from test!');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v20.0/1234567890/messages'
                && $request->hasHeader('Authorization', 'Bearer fake-access-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['recipient_type'] === 'individual'
                && $request['to'] === '628123456789'
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Hello from test!';
        });
    }

    /** @test */
    public function test_it_sends_template_message_successfully()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messaging_product' => 'whatsapp', 'messages' => [['id' => 'wamid.HBgM...']]], 200)
        ]);

        $gateway = Gateway::create([
            'provider' => 'meta',
            'type' => 'whatsapp',
            'is_active' => true,
            'credentials' => [
                'phone_number_id' => '1234567890',
                'token' => 'fake-access-token',
                'template_name' => 'notification_alert',
            ]
        ]);

        $service = new WhatsAppService();
        $result = $service->sendMessage('08123456789', 'Hello from test with template!');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v20.0/1234567890/messages'
                && $request->hasHeader('Authorization', 'Bearer fake-access-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['recipient_type'] === 'individual'
                && $request['to'] === '628123456789'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'notification_alert'
                && $request['template']['language']['code'] === 'id'
                && $request['template']['components'][0]['type'] === 'body'
                && $request['template']['components'][0]['parameters'][0]['type'] === 'text'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Hello from test with template!';
        });
    }

    /** @test */
    public function test_it_handles_failed_api_response()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 400)
        ]);

        $gateway = Gateway::create([
            'provider' => 'meta',
            'type' => 'whatsapp',
            'is_active' => true,
            'credentials' => [
                'phone_number_id' => '1234567890',
                'token' => 'invalid-access-token',
            ]
        ]);

        $service = new WhatsAppService();
        $result = $service->sendMessage('08123456789', 'Fail test');

        $this->assertFalse($result);
    }
}
