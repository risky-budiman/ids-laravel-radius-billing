<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

/**
 * ZTE OLT Telnet Provisioning Service.
 *
 * Operasi TULIS yang butuh CLI (configure terminal): registrasi/hapus ONU, isolir/buka, ganti profil T-CONT.
 * Semua operasi BACA ada di OltGateway (SNMP).
 *
 * Setiap balasan OLT diperiksa: login harus sampai prompt ZXAN, setiap perintah harus dibalas prompt dan tidak
 * berisi pesan error ZTE (%Error / %Code / Invalid input ...). Dulu balasan tidak diperiksa sehingga login gagal
 * atau perintah yang ditolak tetap dianggap berhasil. Alasan gagal tersedia lewat lastError().
 */
class ZteOltProvisioningService
{
    /**
     * Prompt ZTE: "ZXAN#", "ZXAN>", "ZXAN(config)#", "ZXAN(config-if)#".
     * Dulu pola '/(config)#/i' (grup regex = "config#") tidak pernah cocok dengan "ZXAN(config)#", sehingga
     * setiap perintah mode konfigurasi menunggu timeout penuh.
     */
    public const PROMPT = '/ZXAN(\([^)\r\n]*\))?[#>]\s*$/m';

    protected Olt $olt;
    protected $telnet;
    protected ?string $lastError = null;

    public function __construct(Olt $olt, $telnet = null)
    {
        $this->olt = $olt;
        $this->telnet = $telnet ?? new CustomTelnetClient($olt->ip_address, $olt->telnet_port ?? 23);
    }

    /** Alasan kegagalan operasi terakhir (null bila berhasil). */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** Baris pesan error ZTE di balasan perintah, atau null. */
    public static function detectError(string $output): ?string
    {
        if (preg_match('/^\s*(%\s*(Error|Code|Invalid|Unknown|Incomplete|Ambiguous)[^\r\n]*|Error[:\s][^\r\n]*|[^\r\n]*Invalid input detected[^\r\n]*)/mi', $output, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    protected function fail(string $message): bool
    {
        $this->lastError = $message;
        Log::error("OLT {$this->olt->name}: {$message}");
        $this->telnet->disconnect();

        return false;
    }

    protected function connect(): bool
    {
        $this->lastError = null;
        try {
            if (! $this->telnet->connect()) {
                return $this->fail("Tidak bisa terhubung telnet ke {$this->olt->ip_address}:" . ($this->olt->telnet_port ?? 23) . '.');
            }
            usleep(200000);

            $banner = $this->telnet->read('/(Username|Login):/i');
            if (! preg_match('/(Username|Login):/i', $banner)) {
                return $this->fail('OLT tidak menampilkan prompt login telnet.');
            }
            $this->telnet->write($this->olt->username . "\r\n");
            $this->telnet->read('/Password:/i');
            $this->telnet->write($this->olt->password . "\r\n");

            $out = $this->telnet->read([self::PROMPT, '/(Username|Login):/i', '/(fail|incorrect|invalid|denied)/i']);
            if (! preg_match(self::PROMPT, $out)) {
                return $this->fail('Login telnet ditolak OLT (periksa username/password OLT).');
            }

            if (preg_match('/ZXAN>\s*$/m', $out)) {
                $this->telnet->write("enable\r\n");
                $res = $this->telnet->read(['/Password:/i', self::PROMPT]);
                if (stripos($res, 'Password:') !== false) {
                    usleep(500000);
                    $this->telnet->write(($this->olt->enable_password ?: $this->olt->password) . "\r\n");
                    $res = $this->telnet->read([self::PROMPT, '/(fail|incorrect|invalid|denied)/i']);
                }
                if (! preg_match('/ZXAN#\s*$/m', $res)) {
                    return $this->fail('Mode enable ditolak OLT (periksa Enable Password).');
                }
            }

            $this->telnet->write("terminal length 0\r\n");
            $this->telnet->read(self::PROMPT);

            return true;
        } catch (\Throwable $e) {
            return $this->fail('Koneksi telnet gagal: ' . $e->getMessage());
        }
    }

    /**
     * Jalankan perintah satu per satu; berhenti di perintah pertama yang tidak dibalas atau ditolak OLT.
     *
     * @throws \RuntimeException
     */
    protected function run(array $commands): array
    {
        $outputs = [];
        foreach ($commands as $cmd) {
            $this->telnet->write($cmd . "\n");
            usleep(200000);
            $out = $this->telnet->read(self::PROMPT);
            if ($err = self::detectError($out)) {
                throw new \RuntimeException("Perintah '{$cmd}' ditolak OLT: {$err}");
            }
            if (! preg_match(self::PROMPT, $out)) {
                throw new \RuntimeException("OLT tidak membalas perintah '{$cmd}' (timeout).");
            }
            $outputs[$cmd] = $out;
        }

        return $outputs;
    }

    /** Hubungkan, jalankan perintah, putuskan. */
    protected function execute(string $label, array $commands): bool
    {
        if (! $this->connect()) {
            return false;
        }
        try {
            $this->run($commands);
            $this->telnet->disconnect();
            Log::info("OLT {$this->olt->name}: {$label} berhasil.");

            return true;
        } catch (\Throwable $e) {
            return $this->fail("{$label} gagal — " . $e->getMessage());
        }
    }

    /** ID ONU pertama yang kosong di port (sesi telnet harus sudah terhubung). */
    protected function findFirstFreeOnuId($shelf, $slot, $port): int
    {
        $out = $this->run(["show gpon onu state gpon-olt_{$shelf}/{$slot}/{$port}"]);
        $output = (string) reset($out);

        $usedIds = [];
        if (preg_match_all('/gpon-onu_\d+\/\d+\/\d+:(\d+)/i', $output, $m)) {
            $usedIds = array_map('intval', $m[1]);
        } elseif (preg_match_all('/:\s*(\d+)\s+/i', $output, $m)) {
            $usedIds = array_map('intval', $m[1]);
        }
        for ($i = 1; $i <= 128; $i++) {
            if (! in_array($i, $usedIds, true)) {
                return $i;
            }
        }

        throw new \RuntimeException("Port {$shelf}/{$slot}/{$port} penuh (128 ONU).");
    }

    /**
     * Registrasi ONU. Mengembalikan posisi "shelf/slot/port:onu" atau false (alasan di lastError()).
     */
    public function provisionOnu($shelf, $slot, $port, $sn, $onuType, $vlan = 100, $description = 'NextLink-Customer', ?string $tcontProfile = null)
    {
        $this->lastError = null;
        // Nilai yang masuk ke perintah CLI: SN/tipe/profil satu kata, VLAN angka, nama tanpa spasi.
        // Baris baru atau spasi di nilai ini bisa memecah/menyisipkan perintah OLT.
        $sn = strtoupper(trim((string) $sn));
        if (! preg_match('/^[A-Z0-9]{8,16}$/', $sn) || ! preg_match('/^[A-Za-z0-9_.\-]{1,40}$/', (string) $onuType)) {
            $this->lastError = "SN '{$sn}' atau tipe ONU '{$onuType}' tidak valid.";
            Log::error("Provisioning ditolak: {$this->lastError}");

            return false;
        }
        $vlan = max(1, min(4094, (int) $vlan));
        $tcontProfile = $tcontProfile && preg_match('/^[A-Za-z0-9_.\-]{1,50}$/', $tcontProfile) ? $tcontProfile : 'UP-100M';
        $description = trim(preg_replace('/[^A-Za-z0-9_.\-]+/', '_', (string) $description), '_') ?: 'Customer';
        $description = substr($description, 0, 32);

        if (! $this->connect()) {
            return false;
        }

        try {
            $onuId = $this->findFirstFreeOnuId($shelf, $slot, $port);
            $oltPort = "gpon-olt_{$shelf}/{$slot}/{$port}";
            $onuPort = "gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}";
            Log::info("Provisioning ONU {$sn} on {$onuPort} with VLAN {$vlan}");

            $this->run([
                'conf t',
                "interface {$oltPort}",
                "onu {$onuId} type {$onuType} sn {$sn}",
                'exit',
                "interface {$onuPort}",
                "name {$description}",
                "description {$description}",
                'sn-bind enable sn',
                "tcont 1 profile {$tcontProfile}",
                'gemport 1 tcont 1',
                'gemport 1 traffic-limit upstream default downstream default',
                'exit',
                "interface vport-{$onuPort}.1",
                "service-port 1 user-vlan {$vlan} vlan {$vlan}",
                'exit',
                "pon-onu-mng {$onuPort}",
                "service HSI gemport 1 vlan {$vlan}",
                'exit',
                'write',
            ]);
            $this->telnet->disconnect();

            return "{$shelf}/{$slot}/{$port}:{$onuId}";
        } catch (\Throwable $e) {
            $this->fail('Registrasi ONU gagal — ' . $e->getMessage());

            return false;
        }
    }

    public function deleteOnu($shelf, $slot, $port, $onuId)
    {
        return $this->execute("Hapus ONU {$shelf}/{$slot}/{$port}:{$onuId}", [
            'conf t', "interface gpon-olt_{$shelf}/{$slot}/{$port}", "no onu {$onuId}", 'exit', 'write',
        ]);
    }

    public function suspendOnu($shelf, $slot, $port, $onuId)
    {
        return $this->execute("Isolir ONU {$shelf}/{$slot}/{$port}:{$onuId}", [
            'conf t', "interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}", 'shutdown', 'exit', 'write',
        ]);
    }

    public function resumeOnu($shelf, $slot, $port, $onuId)
    {
        return $this->execute("Buka ONU {$shelf}/{$slot}/{$port}:{$onuId}", [
            'conf t', "interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}", 'no shutdown', 'exit', 'write',
        ]);
    }

    public function updateOnuProfile($shelf, $slot, $port, $onuId, $package)
    {
        $this->lastError = null;
        $profileName = is_string($package) ? $package : ($package->olt_tcont_profile ?? $package->bandwidth_profile ?? 'UP-100M');
        if (! preg_match('/^[A-Za-z0-9_.\-]{1,50}$/', (string) $profileName)) {
            $this->lastError = "Nama profil T-CONT '{$profileName}' tidak valid.";

            return false;
        }

        return $this->execute("Ganti profil ONU {$shelf}/{$slot}/{$port}:{$onuId} ke {$profileName}", [
            'conf t', "interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}", "tcont 1 profile {$profileName}", 'exit', 'write',
        ]);
    }

    /** Tes koneksi telnet (login sampai prompt). */
    public function testConnection()
    {
        if (! $this->connect()) {
            throw new \RuntimeException($this->lastError ?? 'Koneksi telnet gagal.');
        }
        $this->telnet->disconnect();

        return true;
    }
}
