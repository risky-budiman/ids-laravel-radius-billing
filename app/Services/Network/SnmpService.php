<?php

namespace App\Services\Network;

use App\Models\Olt;
use FreeDSx\Snmp\SnmpClient;
use Exception;
use Illuminate\Support\Facades\Log;

class SnmpService
{
    protected $client;
    protected $host;
    protected $port;
    protected $community;
    protected $version;

    public function __construct(string $host, string $community = 'public', int $port = 161, int $version = 2)
    {
        $this->host = $host;
        $this->port = $port ?: 161;
        $this->community = $community ?: 'public';
        $this->version = $version ?: 2;

        $this->client = new SnmpClient([
            'host' => $this->host,
            'port' => $this->port,
            'community' => $this->community,
            'version' => $this->version,
            'timeout' => 2,
            'retries' => 1,
        ]);
    }

    /**
     * Create SnmpService from an Olt model instance.
     */
    public static function fromOlt(Olt $olt): self
    {
        return new self(
            $olt->ip_address,
            $olt->snmp_read_community ?: 'public',
            (int)($olt->snmp_port ?: 161),
            (int)($olt->snmp_version ?: 2)
        );
    }

    /**
     * Perform an SNMP GET request.
     */
    public function get(string $oid)
    {
        $cleanOid = ltrim($oid, '.');

        try {
            $response = $this->client->getValue($cleanOid);
            return $response;
        } catch (Exception $e) {
            // Fallback for Linux environments if FreeDSx has socket issue
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    $cmd = "snmpget -On -v{$this->version}c -c " . escapeshellarg($this->community) . " -t 3 -r 1 {$this->host}:{$this->port} {$oid} 2>&1";
                    $output = shell_exec($cmd);
                    if ($output && preg_match('/=\s*(\w+):\s*(.*)/i', $output, $matches)) {
                        return trim($matches[2], '" ');
                    }
                } catch (\Exception $systemEx) {
                    Log::debug("System snmpget failed: " . $systemEx->getMessage());
                }
            }

            Log::warning("SNMP GET Failed for {$this->host}:{$this->port} (v{$this->version}c, {$this->community}) OID {$oid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP WALK request.
     * Always returns associative array with OIDs starting with a leading dot '.1.3.6...'
     */
    public function walk(string $oid): array
    {
        $cleanOid = ltrim($oid, '.');

        try {
            $walk = $this->client->walk($cleanOid);
            $results = [];
            
            foreach ($walk as $item) {
                $oidStr = '.' . ltrim($item->getOid()->toString(), '.');
                $val = $item->getValue();
                $results[$oidStr] = is_object($val) && method_exists($val, 'getValue') ? $val->getValue() : $val;
            }
            
            return $results;
        } catch (Exception $e) {
            // Fallback for Linux environments
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    $cmd = "snmpwalk -On -v{$this->version}c -c " . escapeshellarg($this->community) . " -t 5 -r 1 {$this->host}:{$this->port} {$oid} 2>&1";
                    $output = shell_exec($cmd);
                    if ($output && !str_contains($output, 'No response') && !str_contains($output, 'Timeout')) {
                        $results = [];
                        if (preg_match_all('/\.?(\d+(?:\.\d+)*)\s+=\s+(\w+):\s+(.*)/i', $output, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $m) {
                                $results['.' . $m[1]] = trim($m[3], '" ');
                            }
                        }
                        if (!empty($results)) return $results;
                    }
                } catch (\Exception $systemEx) {
                    Log::debug("System snmpwalk failed: " . $systemEx->getMessage());
                }
            }

            Log::warning("SNMP WALK Failed for {$this->host}:{$this->port} OID {$oid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP SET request.
     */
    public function set(string $oid, $value, string $type = 'string')
    {
        try {
            $cleanOid = ltrim($oid, '.');
            $this->client->set($cleanOid, $value);
            return true;
        } catch (Exception $e) {
            Log::error("SNMP SET Error for {$this->host}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test connection to the device.
     */
    public function testConnection(): array
    {
        try {
            $name = $this->getSafe('.1.3.6.1.2.1.1.5.0');
            $descr = $this->getSafe('.1.3.6.1.2.1.1.1.0');
            
            if ($name !== null || $descr !== null) {
                return [
                    'status' => true,
                    'message' => 'Connected successfully via SNMP',
                    'device_name' => (string)($name ?: 'ZTE OLT'),
                    'description' => (string)($descr ?: '')
                ];
            }

            // If sysName failed, try sysUpTime
            $uptime = $this->getSafe('.1.3.6.1.2.1.1.3.0');
            if ($uptime !== null) {
                return [
                    'status' => true,
                    'message' => 'Connected successfully via SNMP (sysUpTime responsive)',
                    'device_name' => 'ZTE OLT',
                    'description' => 'Up: ' . $uptime
                ];
            }

            return [
                'status' => false,
                'message' => "No SNMP response from {$this->host}:{$this->port}. Please verify IP, SNMP Port, Community string, and firewall/ACL."
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => "SNMP Query Failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Safe GET — returns null on failure instead of throwing.
     */
    public function getSafe(string $oid)
    {
        try {
            return $this->get($oid);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Safe WALK — returns empty array on failure instead of throwing.
     */
    public function walkSafe(string $oid): array
    {
        try {
            return $this->walk($oid);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getHost()
    {
        return $this->host;
    }
}
