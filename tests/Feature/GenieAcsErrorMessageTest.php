<?php

namespace Tests\Feature;

use App\Services\GenieACSService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Galat GenieACS tampil dalam bahasa Indonesia; waktu habis tetap dikenali sebagai "perintah antre". */
class GenieAcsErrorMessageTest extends TestCase
{
    private function message(callable $fn): string
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
        $this->fail('Tidak ada exception');
    }

    public function test_http_and_connection_errors_are_indonesian(): void
    {
        $acs = new GenieACSService('http://acs.test:7557');

        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);
        $this->assertSame('GenieACS mengalami galat (HTTP 500): boom', $this->message(fn () => $acs->getDevices()));

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response('<html>Not Found</html>', 404)]);
        $this->assertStringStartsWith('Data tidak ditemukan di GenieACS (HTTP 404)', $this->message(fn () => $acs->getDevices()));

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 7: Failed to connect to acs.test port 7557')]);
        $this->assertStringStartsWith('Tidak bisa terhubung ke GenieACS di http://acs.test:7557', $this->message(fn () => $acs->getDevices()));

        $this->assertSame('Alamat server GenieACS belum diisi.', $this->message(fn () => (new GenieACSService())->getDevices()));
    }

    public function test_timeout_still_means_queued(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 8001 milliseconds')]);
        $acs = new GenieACSService('http://acs.test:7557');
        $dev = ['InternetGatewayDevice' => ['LANDevice' => ['1' => ['WLANConfiguration' => ['1' => [
            'SSID' => ['_value' => 'lama', '_writable' => true, '_type' => 'xsd:string']]]]]]];

        $r = $acs->applyParameters('DEV-1', $dev, ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID' => 'baru']);
        $this->assertSame('queued', $r['state']);
    }
}
