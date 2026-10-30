<?php

namespace App\Services\Network;

use App\Models\Olt;
use Exception;
use Illuminate\Support\Facades\Log;

class HsgqCliService
{
    protected Olt $olt;
    protected $socket = null;
    protected int $timeout = 6;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
    }

    public function connect(): bool
    {
        $host = $this->olt->ip_address;
        $port = $this->olt->telnet_port ?: 23;
        $user = $this->olt->username ?: 'risky';
        $pass = $this->olt->password ?: 'risky';

        $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            Log::warning("HSGQ Telnet failed to connect to {$host}:{$port}");
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        // 1. Read initial banner & wait for username
        $this->readUntil(['username:', 'login:']);
        fwrite($this->socket, $user . "\r\n");

        // 2. Wait for password
        $this->readUntil(['password:', 'Password:']);
        fwrite($this->socket, $pass . "\r\n");

        // 3. Wait for prompt
        $res = $this->readUntil(['>', '#']);

        // 4. Enter enable mode if at '>'
        if (str_ends_with($res, '>')) {
            fwrite($this->socket, "enable\r\n");
            $enRes = $this->readUntil(['#', 'Password:', 'password:']);
            if (str_contains($enRes, 'assword:')) {
                $enPass = $this->olt->enable_password ?: $pass;
                fwrite($this->socket, $enPass . "\r\n");
                $this->readUntil('#');
            }
        }

        // 5. Enter configure mode to execute show commands
        fwrite($this->socket, "configure\r\n");
        $this->readUntil('(config)#');

        // 6. Disable pagination so output flows completely
        fwrite($this->socket, "terminal length 0\r\n");
        usleep(150000);
        $this->readUntil('(config)#');

        return true;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "exit\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    protected function readUntil($targets, int $maxSeconds = 6): string
    {
        if (!$this->socket) return '';
        $out = '';
        $startTime = microtime(true);
        while (!feof($this->socket) && (microtime(true) - $startTime) < $maxSeconds) {
            $c = fgetc($this->socket);
            if ($c === false) break;
            $out .= $c;
            foreach ((array)$targets as $t) {
                if (str_ends_with($out, $t)) return $out;
            }
        }
        return $out;
    }

    protected function executeCommand(string $cmd): string
    {
        if (!$this->socket) return '';
        fwrite($this->socket, $cmd . "\r\n");
        $buf = '';
        $start = microtime(true);
        while (!feof($this->socket) && (microtime(true) - $start < 12)) {
            $chunk = fread($this->socket, 4096);
            if ($chunk !== false && strlen($chunk) > 0) {
                $buf .= $chunk;
                if (str_contains($buf, '(config)#')) break;
            }
            usleep(50000);
        }
        return $buf;
    }

    /**
     * Get all ONUs on a specific PON port with MAC, Name, Status, and Optical Rx/Tx dBm!
     */
    public function getOnusOnPort(int $portNum): array
    {
        if (!$this->connect()) {
            return [];
        }

        // 1. Get Optical Diagnostics per port
        $diagOutput = $this->executeCommand("show optical-diag {$portNum}");

        // 2. Get ONU Info for this specific port
        $infoOutput = $this->executeCommand("show onu-info {$portNum} all");

        $this->disconnect();

        // Parse Optical Diagnostics:
        // Format: 1/1 20391582228 00:1a:69:43:12:29 45 °C 3.29 V 12 mA 1.7629 dBm -15.7840 dBm
        $opticalMap = [];
        $diagLines = explode("\n", $diagOutput);
        foreach ($diagLines as $line) {
            $line = trim($line);
            if (preg_match('/^(\d+)\/(\d+)\s+(\S+)\s+([0-9a-fA-F:]{17})\s+.*?(-?\d+(?:\.\d+)?\s*dBm)\s+(-?\d+(?:\.\d+)?\s*dBm)/i', $line, $m)) {
                $onuId = (int)$m[2];
                $opticalMap[$onuId] = [
                    'tx_power' => $m[5],
                    'rx_power' => $m[6],
                ];
            }
        }

        // Parse ONU Info:
        // Format: 1/1 00:1a:69:43:12:29 Online TRUE TRUE 2026/10/27 16:51:44 20391582228 Ilhamsyah Pu
        $results = [];
        $infoLines = explode("\n", $infoOutput);
        foreach ($infoLines as $line) {
            $line = trim($line);
            if (preg_match('/^(\d+)\/(\d+)\s+([0-9a-fA-F:]{17})\s+(\w+)\s+(?:TRUE|FALSE)\s+(?:TRUE|FALSE)\s+(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+(\S+)(?:\s+(.*))?/i', $line, $m)) {
                $p = (int)$m[1];
                $onuId = (int)$m[2];
                if ($p !== $portNum) continue;

                $mac = strtolower(trim($m[3]));
                $rawStatus = strtolower(trim($m[4]));
                $status = ($rawStatus === 'online') ? 'online' : 'offline';
                $regTime = trim($m[5]);
                $onuName = trim($m[6]);
                $onuDesc = isset($m[7]) ? trim($m[7]) : '';

                // Get Rx signal from opticalMap
                $signalVal = 'N/A';
                if (isset($opticalMap[$onuId])) {
                    $rawRx = $opticalMap[$onuId]['rx_power'];
                    if (preg_match('/(-?\d+(?:\.\d+)?)/', $rawRx, $sm)) {
                        $signalVal = (float)$sm[1];
                    }
                }

                $displayName = ($onuDesc && $onuDesc !== 'NO-DESCRIPTI') ? $onuDesc : $onuName;

                $results[] = [
                    'index'       => "1.1.{$portNum}.{$onuId}",
                    'onu_id'      => $onuId,
                    'sn'          => $mac, // MAC address dijadikan identifier SN
                    'name'        => $displayName,
                    'type'        => 'EPON ONU',
                    'status'      => $status,
                    'signal'      => $signalVal,
                    'reg_time'    => $regTime,
                    'description' => $onuDesc,
                ];
            }
        }

        return $results;
    }
}
