<?php

namespace App\Services\Network;

use App\Models\Olt;
use FreeDSx\Snmp\SnmpClient;
use FreeDSx\Snmp\Exception\SnmpRequestException;
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
        $this->version = $version;

        Log::debug("Initializing SNMP Client: {$this->host}:{$this->port} (v{$this->version}c, community: {$this->community})");

        $this->client = new SnmpClient([
            'host' => $this->host,
            'port' => $this->port,
            'community' => $this->community,
            'version' => $this->version,
            'timeout' => 10,
            'retries' => 3,
        ]);
    }

    /**
     * Create SnmpService from an Olt model instance.
     */
    public static function fromOlt(Olt $olt): self
    {
        return new self(
            $olt->ip_address,
            $olt->snmp_read_community,
            $olt->snmp_port ?? 161,
            $olt->snmp_version ?? 2
        );
    }

    /**
     * Perform an SNMP GET request.
     */
    public function get(string $oid)
    {
        // Normalize OID (remove leading dot for library)
        $cleanOid = ltrim($oid, '.');

        try {
            $response = $this->client->getValue($cleanOid);
            return $response;
        } catch (Exception $e) {
            // Fallback for Ubuntu/Linux: Try system snmpget
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    $cmd = "snmpget -On -v{$this->version}c -c {$this->community} -t 2 -r 1 {$this->host} {$oid} 2>&1";
                    $output = shell_exec($cmd);
                    if ($output && preg_match('/= (\w+): (.*)/i', $output, $matches)) {
                        return trim($matches[2], '" ');
                    }
                } catch (\Exception $systemEx) {
                    Log::debug("System snmpget also failed: " . $systemEx->getMessage());
                }
            }

            Log::error("SNMP GET Error for {$this->host} OID {$oid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP WALK request.
     */
    public function walk(string $oid)
    {
        // Normalize OID (remove leading dot for library)
        $cleanOid = ltrim($oid, '.');

        try {
            $walk = $this->client->walk($cleanOid);
            $results = [];
            
            foreach ($walk as $item) {
                $results[$item->getOid()->toString()] = $item->getValue()->getValue();
            }
            
            return $results;
        } catch (Exception $e) {
            // Fallback for Ubuntu/Linux: Try system snmpwalk
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    $cmd = "snmpwalk -On -v{$this->version}c -c {$this->community} -t 5 -r 1 {$this->host} {$oid} 2>&1";
                    $output = shell_exec($cmd);
                    if ($output && !str_contains($output, 'No response')) {
                        $results = [];
                        // Parse format: .1.3.6... = STRING: "VALUE" or INTEGER: 25
                        // Also support outputs that don't start with a dot
                        if (preg_match_all('/\.?(\d+(?:\.\d+)*)\s+=\s+(\w+):\s+(.*)/i', $output, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $m) {
                                $results['.' . $m[1]] = trim($m[3], '" ');
                            }
                        }
                        if (!empty($results)) return $results;
                    }
                } catch (\Exception $systemEx) {
                    Log::debug("System snmpwalk also failed: " . $systemEx->getMessage());
                }
            }

            Log::error("SNMP WALK Error for {$this->host} OID {$oid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP SET request.
     */
    public function set(string $oid, $value, string $type = 'string')
    {
        try {
            // FreeDSx/SNMP handles types automatically in most cases, 
            // but we might need to be specific if needed.
            $this->client->set($oid, $value);
            return true;
        } catch (Exception $e) {
            Log::error("SNMP SET Error for {$this->host}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test connection to the device.
     */
    public function testConnection()
    {
        try {
            // Try to get sysName (1.3.6.1.2.1.1.5.0)
            $name = $this->get('.1.3.6.1.2.1.1.5.0');
            $descr = $this->get('.1.3.6.1.2.1.1.1.0');
            
            return [
                'status' => true,
                'message' => 'Connected successfully',
                'device_name' => (string)$name,
                'description' => (string)$descr
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => "SNMP Query Failed: " . $e->getMessage() . ". Check IP and Community String."
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
