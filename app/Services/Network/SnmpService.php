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

    /**
     * SNMPv3 (USM): user, level (noAuthNoPriv|authNoPriv|authPriv), auth (MD5|SHA|SHA256), priv (DES|AES).
     * v1/v2c mengirim community — termasuk community tulis — tanpa enkripsi.
     */
    protected array $v3 = [];

    public const V3_LEVELS = ['noAuthNoPriv', 'authNoPriv', 'authPriv'];
    public const V3_AUTH = ['MD5', 'SHA', 'SHA256'];
    public const V3_PRIV = ['DES', 'AES'];

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
        $service = new self(
            $olt->ip_address,
            $olt->snmp_read_community ?: 'public',
            (int)($olt->snmp_port ?: 161),
            (int)($olt->snmp_version ?: 2),
            3
        );
        if ((int) $olt->snmp_version === 3) {
            $service->withV3((string) $olt->snmp_v3_user, (string) ($olt->snmp_v3_level ?: 'authPriv'),
                $olt->snmp_v3_auth_protocol, $olt->snmp_v3_auth_password, $olt->snmp_v3_priv_protocol, $olt->snmp_v3_priv_password);
        }

        return $service;
    }

    /** Untuk perintah SET: v1/v2c memakai community tulis; v3 memakai user yang sama (hak tulis diatur di OLT). */
    public static function forWrite(Olt $olt): self
    {
        if ((int) $olt->snmp_version === 3) {
            return self::fromOlt($olt);
        }

        return new self($olt->ip_address, $olt->snmp_write_community ?: 'private', (int) ($olt->snmp_port ?: 161), (int) ($olt->snmp_version ?: 2), 3);
    }

    public function withV3(string $user, string $level = 'authPriv', ?string $authProtocol = 'SHA', ?string $authPassword = null,
        ?string $privProtocol = 'AES', ?string $privPassword = null): self
    {
        $this->version = 3;
        $this->v3 = [
            'user' => $user,
            'level' => in_array($level, self::V3_LEVELS, true) ? $level : 'authPriv',
            'auth_protocol' => in_array($authProtocol, self::V3_AUTH, true) ? $authProtocol : 'SHA',
            'auth_password' => (string) $authPassword,
            'priv_protocol' => in_array($privProtocol, self::V3_PRIV, true) ? $privProtocol : 'AES',
            'priv_password' => (string) $privPassword,
        ];
        $this->client = null;

        return $this;
    }

    protected function v3UsesAuth(): bool
    {
        return in_array($this->v3['level'] ?? '', ['authNoPriv', 'authPriv'], true);
    }

    protected function v3UsesPriv(): bool
    {
        return ($this->v3['level'] ?? '') === 'authPriv';
    }

    /**
     * Argumen versi & kredensial untuk perintah net-snmp. Dulu v1 menghasilkan "-v1c" (tidak valid).
     */
    public function cliAuthArgs(): string
    {
        if ($this->version === 3) {
            $args = '-v3 -u ' . escapeshellarg($this->v3['user'] ?? '') . ' -l ' . escapeshellarg($this->v3['level'] ?? 'authPriv');
            if ($this->v3UsesAuth()) {
                $args .= ' -a ' . ($this->v3['auth_protocol'] === 'SHA256' ? 'SHA-256' : $this->v3['auth_protocol'])
                    . ' -A ' . escapeshellarg($this->v3['auth_password']);
            }
            if ($this->v3UsesPriv()) {
                $args .= ' -x ' . $this->v3['priv_protocol'] . ' -X ' . escapeshellarg($this->v3['priv_password']);
            }

            return $args;
        }

        return ($this->version === 1 ? '-v1' : '-v2c') . ' -c ' . escapeshellarg($this->community);
    }

    /** Opsi FreeDSx untuk versi yang dipakai. */
    public function freeDsxOptions(): array
    {
        $options = ['host' => $this->host, 'port' => $this->port, 'version' => $this->version, 'timeout' => $this->timeout, 'retries' => 1];
        if ($this->version !== 3) {
            return $options + ['community' => $this->community];
        }

        return $options + [
            'user' => $this->v3['user'] ?? '',
            'use_auth' => $this->v3UsesAuth(),
            'auth_mech' => $this->v3UsesAuth() ? ['MD5' => 'md5', 'SHA' => 'sha1', 'SHA256' => 'sha256'][$this->v3['auth_protocol']] : null,
            'auth_pwd' => $this->v3UsesAuth() ? $this->v3['auth_password'] : null,
            'use_priv' => $this->v3UsesPriv(),
            'priv_mech' => $this->v3UsesPriv() ? ['DES' => 'des', 'AES' => 'aes128'][$this->v3['priv_protocol']] : null,
            'priv_pwd' => $this->v3UsesPriv() ? $this->v3['priv_password'] : null,
        ];
    }

    /** Ekstensi php-snmp: GET sesuai versi; null bila tidak tersedia/gagal. */
    protected function nativeGet(string $target, string $oid, int $timeoutMicro): mixed
    {
        if (! extension_loaded('snmp')) {
            return null;
        }
        try {
            if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
            if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            $res = match ($this->version) {
                1 => @snmpget($target, $this->community, $oid, $timeoutMicro, 1),
                3 => @snmp3_get($target, $this->v3['user'], $this->v3['level'], $this->v3['auth_protocol'], $this->v3['auth_password'],
                    $this->v3['priv_protocol'], $this->v3['priv_password'], $oid, $timeoutMicro, 1),
                default => @snmp2_get($target, $this->community, $oid, $timeoutMicro, 1),
            };

            return $res === false ? null : $res;
        } catch (\Throwable $e) {
            Log::debug('Native SNMP get failed: ' . $e->getMessage());

            return null;
        }
    }

    /** Ekstensi php-snmp: WALK sesuai versi; null bila tidak tersedia/gagal. */
    protected function nativeWalk(string $target, string $oid, int $timeoutMicro): ?array
    {
        if (! extension_loaded('snmp')) {
            return null;
        }
        try {
            if (function_exists('snmp_set_quick_print')) snmp_set_quick_print(1);
            if (function_exists('snmp_set_oid_numeric_print')) snmp_set_oid_numeric_print(1);
            if (function_exists('snmp_set_valueretrieval')) snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            $res = match ($this->version) {
                1 => @snmprealwalk($target, $this->community, $oid, $timeoutMicro, 1),
                3 => @snmp3_real_walk($target, $this->v3['user'], $this->v3['level'], $this->v3['auth_protocol'], $this->v3['auth_password'],
                    $this->v3['priv_protocol'], $this->v3['priv_password'], $oid, $timeoutMicro, 1),
                default => @snmp2_real_walk($target, $this->community, $oid, $timeoutMicro, 1),
            };

            return is_array($res) && $res ? $res : null;
        } catch (\Throwable $e) {
            Log::debug('Native SNMP walk failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Get FreeDSx client instance (lazy-loaded).
     */
    protected function getFreeDsxClient(): SnmpClient
    {
        if (!$this->client) {
            $this->client = new SnmpClient($this->freeDsxOptions());
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

        // ── METHOD 1: PHP Native SNMP Extension (v1/v2c/v3) ──
        $res = $this->nativeGet($target, '.' . $cleanOid, $timeoutMicro);
        if ($res !== null) {
            return trim((string) $res, "\" \r\n");
        }

        // ── METHOD 2: System CLI snmpget (Linux / macOS) ──
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $cmd = "snmpget -On " . $this->cliAuthArgs() . " -t {$this->timeout} -r 1 " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " 2>&1";
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
            Log::warning("SNMP GET Failed for {$target} (SNMP v{$this->version}) OID .{$cleanOid}: " . $e->getMessage());
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

        // ── METHOD 1: PHP Native SNMP Extension (v1/v2c/v3) ──
        $nativeWalk = $this->nativeWalk($target, '.' . $cleanOid, $timeoutMicro);
        if ($nativeWalk !== null) {
            foreach ($nativeWalk as $k => $v) {
                $results['.' . ltrim($k, '.')] = trim((string) $v, "\" \r\n");
            }

            return $results;
        }

        // ── METHOD 2: System CLI snmpwalk (Linux / macOS) ──
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $cmd = "snmpwalk -On " . $this->cliAuthArgs() . " -t {$this->timeout} -r 1 " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " 2>&1";
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
            // SnmpWalk diiterasi dengan hasOids()/next() — dulu di-foreach langsung sehingga selalu kosong
            $walk = $this->getFreeDsxClient()->walk($cleanOid);
            while ($walk->hasOids()) {
                $item = $walk->next();
                $oidStr = '.' . ltrim((string) $item->getOid(), '.');
                if (! str_starts_with($oidStr, '.' . $cleanOid . '.')) {
                    break;   // keluar dari subtree
                }
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
        if (extension_loaded('snmp')) {
            try {
                $typeChar = is_int($value) ? 'i' : 's';
                $res = match ($this->version) {
                    1 => @snmpset($target, $this->community, '.' . $cleanOid, $typeChar, $value, $this->timeout * 1000000, 1),
                    3 => @snmp3_set($target, $this->v3['user'], $this->v3['level'], $this->v3['auth_protocol'], $this->v3['auth_password'],
                        $this->v3['priv_protocol'], $this->v3['priv_password'], '.' . $cleanOid, $typeChar, $value, $this->timeout * 1000000, 1),
                    default => @snmp2_set($target, $this->community, '.' . $cleanOid, $typeChar, $value, $this->timeout * 1000000, 1),
                };
                if ($res) return true;
            } catch (\Throwable $e) {}
        }

        // 2. Try System CLI
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            try {
                $typeChar = is_int($value) ? 'i' : 's';
                $cmd = "snmpset " . $this->cliAuthArgs() . " " . escapeshellarg($target) . " " . escapeshellarg('.' . $cleanOid) . " {$typeChar} " . escapeshellarg((string)$value) . " 2>&1";
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
                'message' => "OLT {$this->host}:{$this->port} tidak menjawab SNMP v{$this->version}. Periksa routing, firewall, ACL SNMP dan kredensial (community / user SNMPv3)."
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

    /**
     * Walk kolom biner (mis. MAC) dengan nilai selalu berupa hex huruf kecil tanpa pemisah ("001a69431229").
     *
     * walk() biasa tidak cocok untuk data biner: ekstensi PHP & FreeDSx mengembalikan byte mentah, sedangkan
     * snmpwalk CLI menampilkan "Hex-STRING: 00 1A …" atau — bila byte-nya kebetulan bisa dicetak — STRING acak.
     */
    public function walkHexSafe(string $oid): array
    {
        $cleanOid = ltrim($oid, '.');
        $raw = [];
        try {
            if (! extension_loaded('snmp') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // CLI dengan -Ox: semua OCTET STRING ditampilkan sebagai hex
                $cmd = "snmpwalk -On -Ox " . $this->cliAuthArgs() . " -t {$this->timeout} -r 1 "
                    . escapeshellarg($this->getTargetAddress()) . ' ' . escapeshellarg('.' . $cleanOid) . ' 2>/dev/null';
                $output = (string) shell_exec($cmd);
                if (preg_match_all('/^\.?(\d+(?:\.\d+)*)\s+=\s+Hex-STRING:\s*([0-9A-Fa-f ]+)$/m', $output, $m, PREG_SET_ORDER)) {
                    foreach ($m as $row) {
                        $raw['.' . $row[1]] = strtolower(str_replace(' ', '', trim($row[2])));
                    }

                    return $raw;
                }
            }
            foreach ($this->walk($cleanOid) as $k => $v) {
                $v = (string) $v;
                $raw[$k] = preg_match('/^([0-9A-Fa-f]{2}[ :]?)+$/', trim($v)) && strlen(trim($v)) > 2 * 6 - 1 && preg_match('/[ :]/', $v)
                    ? strtolower(preg_replace('/[^0-9A-Fa-f]/', '', $v))
                    : bin2hex($v);
            }
        } catch (\Throwable) {
            return [];
        }

        return $raw;
    }

    public function getHost(): string
    {
        return $this->host;
    }
}
