<?php

namespace App\Services\Network;

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
        $this->port = $port;
        $this->community = $community;
        $this->version = $version;

        $this->client = new SnmpClient([
            'host' => $host,
            'port' => $port,
            'community' => $community,
            'version' => $version,
            'timeout' => 3,
            'retries' => 1,
        ]);
    }

    /**
     * Perform an SNMP GET request.
     */
    public function get(string $oid)
    {
        try {
            $response = $this->client->getValue($oid);
            return $response;
        } catch (SnmpRequestException $e) {
            Log::error("SNMP GET Error for {$this->host}: " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error("General SNMP Error for {$this->host}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform an SNMP WALK request.
     */
    public function walk(string $oid)
    {
        try {
            $walk = $this->client->walk($oid);
            $results = [];
            
            while ($walk->hasItems()) {
                $item = $walk->next();
                $results[$item->getOid()->toString()] = $item->getValue()->getValue();
            }
            
            return $results;
        } catch (Exception $e) {
            Log::error("SNMP WALK Error for {$this->host}: " . $e->getMessage());
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
            $name = $this->get('1.3.6.1.2.1.1.5.0');
            return [
                'status' => true,
                'message' => 'Connected successfully',
                'device_name' => (string)$name
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
