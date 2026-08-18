<?php

namespace App\Services\Network;

use App\Models\Olt;
use FreeDSx\Snmp\SnmpClient;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Universal SNMP Service
 * 
 * Uses a robust 3-tier execution strategy:
 * 1. PHP Native ext-snmp (snmp2_get / snmp2_real_walk) — Fastest & most standard
 * 2. System CLI (snmpget / snmpwalk) — 100% compatible with CLI tools
 * 3. FreeDSx/SNMP pure PHP library — Userspace fallback
 */
class SnmpService
{
    protected ?SnmpClient $client = null;
    protected string $host;
    protected int $port;
    protected string $community;
    protected int $version;
    protected int $timeout; // seconds

    public function __construct(string $host, string $community = 'public', int $port = 161, int $version = 2, int $timeout = 3)
    {
        $this->host = trim($host);
        $this->port = $port ?: 161;
        $this->community = trim($community) ?: 'public';
        $this->version = $version ?: 2;
        $this->timeout = $timeout ?: 3;
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
            (int)($olt->snmp_version ?: 2),
            3
        );
    }

    /**
     * Get FreeDSx client instance (lazy-loaded).
     */
    protected function getFreeDsxClient(): SnmpClient
    {
        if (!$this->client) {
            $this->client = new SnmpClient([
                'host'      => $this->host,
                'port'      => $this->port,
                'community' => $this->community,
                'version'   => $this->version,
                'timeout'   => $this->timeout,
                'retries'   => 1,
            ]);
        }
        return $this->client;
    }

    /**
     * Target address formatted for SNMP tools (host or host:port).
     */
    protected function getTargetAddress(): string
    {
        return $this->port == 161 ? $this->host : "{$this->host}:{$this->port}";
    }

    /**
     * Perform an SNMP GET request.
     */
    public function get(string $oid)
    {
        $cleanOid = ltrim($oid, '.');
        $target = $this->getTargetAddress();
        $timeoutMicro = $this->timeout * 1000000;

        // ── METHOD 1: PHP Native SNMP Extension ──
        if (function_exists('snmp2_get') && $this->version == 2) {
            try {
                if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
                if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $res = @snmp2_get($target, $this->community, '.' . $cleanOid, $timeoutMicro, 1);
                if ($res !== false && $res !== null) {
                    return trim((string)$res, "\" \r\n");
                }
            } catch (\Throwable $e) {
                Log::debug("Native snmp2_get failed: " . $e->getMessage());
            }
        } elseif (function_exists('snmpget') && $this->version == 1) {
            try {
                if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
                if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $res = @snmpget($target, $this->community, '.' . $cleanOid, $timeoutMicro, 1);
                if ($res !== false && $res !== null) {
                    return trim((string)$res, "\" \r\n");
                }
            } catch (\Throwable $e) {}
        }

        // ── METHOD 2: System CLI snmpget (Linux / macOS) ──
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $cmd = "snmpget -On -v{$this->version}c -c " . escapeshellarg($this->community) . " -t {$this->timeout} -r 1 " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " 2>&1";
                $output = shell_exec($cmd);
                if ($output && preg_match('/=\s*(\w+):\s*(.*)/i', $output, $matches)) {
                    return trim($matches[2], "\" \r\n");
                }
            } catch (\Throwable $systemEx) {
                Log::debug("System snmpget failed: " . $systemEx->getMessage());
            }
        }

        // ── METHOD 3: FreeDSx Pure PHP Library ──
        try {
            return $this->getFreeDsxClient()->getValue($cleanOid);
        } catch (Exception $e) {
            Log::warning("SNMP GET Failed for {$target} (v{$this->version}c, {$this->community}) OID .{$cleanOid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP WALK request.
     * Always returns an associative array where keys are OIDs with leading dot (e.g. '.1.3.6.1...').
     */
    public function walk(string $oid): array
    {
        $cleanOid = ltrim($oid, '.');
        $target = $this->getTargetAddress();
        $timeoutMicro = $this->timeout * 1000000;
        $results = [];

        // ── METHOD 1: PHP Native SNMP Extension ──
        if (function_exists('snmp2_real_walk') && $this->version == 2) {
            try {
                if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
                if (function_exists('snmp_set_oid_numeric_print')) snmp_set_oid_numeric_print(1);
                if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $nativeWalk = @snmp2_real_walk($target, $this->community, '.' . $cleanOid, $timeoutMicro, 1);

                if (is_array($nativeWalk) && !empty($nativeWalk)) {
                    foreach ($nativeWalk as $k => $v) {
                        $key = '.' . ltrim($k, '.');
                        $results[$key] = trim((string)$v, "\" \r\n");
                    }
                    return $results;
                }
            } catch (\Throwable $e) {
                Log::debug("Native snmp2_real_walk failed: " . $e->getMessage());
            }
        } elseif (function_exists('snmprealwalk') && $this->version == 1) {
            try {
                if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
                if (function_exists('snmp_set_oid_numeric_print')) snmp_set_oid_numeric_print(1);
                if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
                $nativeWalk = @snmprealwalk($target, $this->community, '.' . $cleanOid, $timeoutMicro, 1);

                if (is_array($nativeWalk) && !empty($nativeWalk)) {
                    foreach ($nativeWalk as $k => $v) {
                        $key = '.' . ltrim($k, '.');
                        $results[$key] = trim((string)$v, "\" \r\n");
                    }
                    return $results;
                }
            } catch (\Throwable $e) {}
        }

        // ── METHOD 2: System CLI snmpwalk (Linux / macOS) ──
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $cmd = "snmpwalk -On -v{$this->version}c -c " . escapeshellarg($this->community) . " -t {$this->timeout} -r 1 " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " 2>&1";
                $output = shell_exec($cmd);

                if ($output && !str_contains($output, 'No response') && !str_contains($output, 'Timeout') && !str_contains($output, 'Unknown user')) {
                    if (preg_match_all('/\.?(\d+(?:\.\d+)*)\s+=\s+(\w+):\s*(.*)/i', $output, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $m) {
                            $results['.' . $m[1]] = trim($m[3], "\" \r\n");
                        }
                    }
                    if (!empty($results)) return $results;
                }
            } catch (\Throwable $systemEx) {
                Log::debug("System snmpwalk failed: " . $systemEx->getMessage());
            }
        }

        // ── METHOD 3: FreeDSx Pure PHP Library ──
        try {
            $walk = $this->getFreeDsxClient()->walk($cleanOid);
            foreach ($walk as $item) {
                $oidStr = '.' . ltrim($item->getOid()->toString(), '.');
                $val = $item->getValue();
                $results[$oidStr] = is_object($val) && method_exists($val, 'getValue') ? $val->getValue() : $val;
            }
            return $results;
        } catch (Exception $e) {
            Log::warning("SNMP WALK Failed for {$target} OID .{$cleanOid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP SET request.
     */
    public function set(string $oid, $value, string $type = 'string')
    {
        $cleanOid = ltrim($oid, '.');
        $target = $this->getTargetAddress();

        // 1. Try Native PHP ext-snmp
        if (function_exists('snmp2_set') && $this->version == 2) {
            try {
                $typeChar = is_int($value) ? 'i' : 's';
                $res = @snmp2_set($target, $this->community, '.' . $cleanOid, $typeChar, $value, $this->timeout * 1000000, 1);
                if ($res) return true;
            } catch (\Throwable $e) {}
        }

        // 2. Try System CLI
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $typeChar = is_int($value) ? 'i' : 's';
                $cmd = "snmpset -v{$this->version}c -c " . escapeshellarg($this->community) . " " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " {$typeChar} " . escapeshellarg((string)$value) . " 2>&1";
                $output = shell_exec($cmd);
                if ($output && !str_contains($output, 'Error')) return true;
            } catch (\Throwable $e) {}
        }

        // 3. Try FreeDSx
        try {
            $this->getFreeDsxClient()->set($cleanOid, $value);
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
                    'status'      => true,
                    'message'     => 'Connected successfully via SNMP',
                    'device_name' => (string)($name ?: 'ZTE OLT'),
                    'description' => (string)($descr ?: '')
                ];
            }

            $uptime = $this->getSafe('.1.3.6.1.2.1.1.3.0');
            if ($uptime !== null) {
                return [
                    'status'      => true,
                    'message'     => 'Connected successfully via SNMP (sysUpTime responsive)',
                    'device_name' => 'ZTE OLT',
                    'description' => 'Up: ' . $uptime
                ];
            }

            return [
                'status'  => false,
                'message' => "No SNMP response from {$this->host}:{$this->port} with community '{$this->community}'. Check routing, firewall, and SNMP ACL."
            ];
        } catch (Exception $e) {
            return [
                'status'  => false,
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

    public function getHost(): string
    {
        return $this->host;
    }
}
