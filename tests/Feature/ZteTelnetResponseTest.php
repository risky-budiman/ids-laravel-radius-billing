<?php

namespace Tests\Feature;

use App\Jobs\UpdateOnuJob;
use App\Models\ActivityLog;
use App\Models\Olt;
use App\Services\Network\OltGateway;
use App\Services\Network\ZteOltProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Balasan telnet OLT ZTE diperiksa: login gagal, perintah ditolak (%Error) dan timeout menjadi kegagalan dengan
 * alasan; prompt "ZXAN(config)#" dikenali (dulu setiap perintah konfigurasi menunggu timeout).
 */
class ZteTelnetResponseTest extends TestCase
{
    use RefreshDatabase;

    private Olt $olt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olt = Olt::create(['name' => 'OLT ZTE', 'ip_address' => '10.0.0.9', 'snmp_port' => 161, 'snmp_version' => 2,
            'snmp_read_community' => 'public', 'telnet_port' => 23, 'username' => 'zte', 'password' => 'pw', 'enable_password' => 'en',
            'olt_type' => 'ZTE_C320', 'is_active' => true]);
    }

    /**
     * Klien telnet palsu: balasan ditentukan dari perintah terakhir yang dikirim.
     *
     * @param  array<string, string>  $replies  awalan perintah => balasan (tanpa prompt)
     */
    private function telnet(array $replies = [], bool $loginOk = true): object
    {
        return new class($replies, $loginOk) {
            public array $sent = [];
            private string $last = '';
            private int $reads = 0;

            public function __construct(private array $replies, private bool $loginOk) {}
            public function connect() { return true; }
            public function disconnect() {}
            public function write($data) { $this->last = trim($data); $this->sent[] = $this->last; return strlen($data); }
            public function read($regex = null)
            {
                $this->reads++;
                if ($this->reads === 1) return "Welcome\r\nUsername:";
                if ($this->last === 'zte') return 'Password:';
                if ($this->last === 'pw') return $this->loginOk ? "\r\nZXAN#" : "\r\n%Error 10001: Bad password\r\nUsername:";
                foreach ($this->replies as $prefix => $reply) {
                    if (str_starts_with($this->last, $prefix)) {
                        return $this->last . "\r\n" . $reply;
                    }
                }
                $mode = match (true) {
                    $this->last === 'conf t', $this->last === 'exit' => 'ZXAN(config)#',
                    str_starts_with($this->last, 'interface'), str_starts_with($this->last, 'pon-onu-mng') => 'ZXAN(config-if)#',
                    default => 'ZXAN#',
                };

                return $this->last . "\r\n" . $mode;
            }
        };
    }

    public function test_error_lines_are_detected(): void
    {
        $this->assertSame('%Error 20208: The ONU has been registered.', ZteOltProvisioningService::detectError("onu 1 type X sn Y\r\n%Error 20208: The ONU has been registered.\r\nZXAN(config-if)#"));
        $this->assertSame('% Invalid input detected at \'^\' marker.', ZteOltProvisioningService::detectError("tcont 1 profile X\r\n% Invalid input detected at '^' marker.\r\nZXAN(config-if)#"));
        $this->assertNull(ZteOltProvisioningService::detectError("name Error_Budi\r\nZXAN(config-if)#"));
        $this->assertSame(1, preg_match(ZteOltProvisioningService::PROMPT, "conf t\r\nZXAN(config)#"));
        $this->assertSame(1, preg_match(ZteOltProvisioningService::PROMPT, "x\r\nZXAN(config-if)# "));
    }

    public function test_successful_provisioning_returns_position(): void
    {
        $t = $this->telnet(['show gpon onu state' => "gpon-onu_1/1/7:1   enable  working\r\ngpon-onu_1/1/7:2   enable  working\r\nZXAN#"]);
        $svc = new ZteOltProvisioningService($this->olt, $t);

        $this->assertSame('1/1/7:3', $svc->provisionOnu(1, 1, 7, 'ZTEGC1234567', 'ZTE-F660', 200, '001001001863', 'UP-50M'));
        $this->assertNull($svc->lastError());
        $this->assertContains('onu 3 type ZTE-F660 sn ZTEGC1234567', $t->sent);
        $this->assertContains('write', $t->sent);
    }

    public function test_rejected_command_stops_and_reports_reason(): void
    {
        $t = $this->telnet(['onu ' => "%Error 20208: The SN has been registered.\r\nZXAN(config-if)#"]);
        $svc = new ZteOltProvisioningService($this->olt, $t);

        $this->assertFalse($svc->provisionOnu(1, 1, 7, 'ZTEGC1234567', 'ZTE-F660'), 'dulu dianggap berhasil');
        $this->assertStringContainsString('20208', $svc->lastError());
        $this->assertNotContains('write', $t->sent, 'perintah setelah error tidak dijalankan');
    }

    public function test_login_failure_and_silence_are_failures(): void
    {
        $svc = new ZteOltProvisioningService($this->olt, $this->telnet([], loginOk: false));
        $this->assertFalse($svc->suspendOnu(1, 1, 7, 3));
        $this->assertStringContainsString('Login telnet ditolak', $svc->lastError());

        // OLT tidak membalas (tanpa prompt) = timeout, bukan sukses
        $svc = new ZteOltProvisioningService($this->olt, $this->telnet(['shutdown' => '']));
        $this->assertFalse($svc->suspendOnu(1, 1, 7, 3));
        $this->assertStringContainsString("tidak membalas perintah 'shutdown'", $svc->lastError());
    }

    public function test_job_logs_the_reason(): void
    {
        $telnet = $this->telnet(['tcont' => "% Invalid input detected at '^' marker.\r\nZXAN(config-if)#"]);
        $gw = new class($this->olt, $telnet) extends OltGateway {
            public function __construct(Olt $olt, private object $t) { parent::__construct($olt); }
            protected function provisioningService(): ZteOltProvisioningService { return new ZteOltProvisioningService($this->olt, $this->t); }
            public function serialAt(int $shelf, int $slot, int $port, int $onuId): ?string { return 'ZTEGC1234567'; }
        };
        $this->app->bind(OltGateway::class, fn () => $gw);

        (new UpdateOnuJob($this->olt->id, '1/1/7:3', 'update_speed', 'UP-BAD', 'ZTEGC1234567'))->handle();

        $log = ActivityLog::where('action', 'onu_action_failed')->sole();
        $this->assertStringContainsString('Invalid input', $log->description);
    }
}
