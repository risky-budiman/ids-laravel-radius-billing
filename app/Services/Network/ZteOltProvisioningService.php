<?php

namespace App\Services\Network;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

/**
 * ZTE OLT Telnet Provisioning Service.
 * 
 * Handles WRITE operations that require CLI (configure terminal):
 * - ONU Registration / Deregistration
 * - ONU Suspend / Resume
 * - Profile Updates
 * 
 * All READ operations are handled by OltGateway (pure SNMP).
 */
class ZteOltProvisioningService
{
    protected $olt;
    protected $telnet;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->telnet = new CustomTelnetClient($olt->ip_address, $olt->telnet_port ?? 23);
    }

    protected function connect()
    {
        try {
            if (!$this->telnet->connect()) {
                return false;
            }

            // Give OLT a moment to send banner/username prompt
            usleep(200000); // 0.2 seconds

            // Standard Login
            $this->telnet->read('/Username:/i');
            $this->telnet->write($this->olt->username . "\r\n");
            $this->telnet->read('/Password:/i');
            $this->telnet->write($this->olt->password . "\r\n");
            
            $prompt = $this->telnet->read('/ZXAN[>#]/i');
            
            // If we are at ">", we need to send "enable"
            if (strpos($prompt, '>') !== false) {
                $this->telnet->write("enable\r\n");
                $res = $this->telnet->read(['/Password:/i', '/ZXAN#/i']);
                
                if (stripos($res, 'Password:') !== false) {
                    // Give OLT a moment to breathe before sending password
                    usleep(500000); // 0.5 seconds
                    $this->telnet->write(($this->olt->enable_password ?: $this->olt->password) . "\r\n");
                    $this->telnet->read('/ZXAN#/i');
                }
            }
            
            // Turn off pagination
            $this->telnet->write("terminal length 0\r\n");
            $this->telnet->read('/ZXAN#/i');
            
            return true;
        } catch (\Exception $e) {
            Log::error("OLT Telnet Connection Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test the Telnet connection to the OLT
     */
    public function testConnection()
    {
        try {
            $this->connect();
            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT Connection Test Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find the first available ONU ID on a specific port
     */
    public function findFirstFreeOnuId($shelf, $slot, $port)
    {
        if (!$this->connect()) return 1;
        
        $this->telnet->write("show gpon onu state gpon-olt_{$shelf}/{$slot}/{$port}\n");
        $output = $this->telnet->read('/ZXAN#/i');
        
        $usedIds = [];
        // Match lines like: gpon-onu_1/1/1:1   Ready
        if (preg_match_all('/:\s*(\d+)\s+/i', $output, $matches)) {
            $usedIds = array_map('intval', $matches[1]);
        }
        
        for ($i = 1; $i <= 128; $i++) {
            if (!in_array($i, $usedIds)) {
                return $i;
            }
        }
        return 1;
    }

    /**
     * Provision (Activate) an ONU on the OLT
     */
    public function provisionOnu($shelf, $slot, $port, $sn, $onuType, $vlan = 100, $description = 'NextLink-Customer')
    {
        try {
            if (!$this->connect()) return false;
            
            $onuId = $this->findFirstFreeOnuId($shelf, $slot, $port);
            $oltPort = "gpon-olt_{$shelf}/{$slot}/{$port}";
            $onuPort = "gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}";
            
            Log::info("Provisioning ONU {$sn} on {$onuPort} with VLAN {$vlan}");

            $commands = [
                "conf t",
                "interface {$oltPort}",
                "onu {$onuId} type {$onuType} sn {$sn}",
                "exit",
                "interface {$onuPort}",
                "name {$description}",
                "description {$description}",
                "sn-bind enable sn",
                "tcont 1 profile UP-100M", // Assuming default profile exists
                "gemport 1 tcont 1",
                "gemport 1 traffic-limit upstream default downstream default",
                "exit",
                "interface vport-{$onuPort}.1", // Virtual port for VLAN
                "service-port 1 user-vlan {$vlan} vlan {$vlan}", 
                "exit",
                "pon-onu-mng {$onuPort}",
                "service HSI gemport 1 vlan {$vlan}",
                "exit",
                "write" // Save config
            ];
            
            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000); // 0.2s delay for stability
                $this->telnet->read(['/ZXAN#/i', '/(config)#/i', '/(config-if)#/i']);
            }
            
            $this->telnet->disconnect();
            return "{$shelf}/{$slot}/{$port}:{$onuId}";
            
        } catch (\Exception $e) {
            Log::error("Provisioning failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete/Deregister an ONU from the OLT
     */
    public function deleteOnu($shelf, $slot, $port, $onuId)
    {
        try {
            if (!$this->connect()) return false;

            $interface = "gpon-olt_{$shelf}/{$slot}/{$port}";
            Log::info("Deprovisioning ONU {$onuId} on {$interface}");

            $commands = [
                "conf t",
                "interface {$interface}",
                "no onu {$onuId}",
                "exit",
                "write",
            ];

            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000);
                $this->telnet->read(['/ZXAN#/i', '/(config)#/i', '/(config-if)#/i']);
            }

            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT ONU Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Suspend an ONU (disable traffic)
     */
    public function suspendOnu($shelf, $slot, $port, $onuId)
    {
        try {
            if (!$this->connect()) return false;

            $onuPort = "gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}";
            Log::info("Suspending ONU {$onuPort}");

            $commands = [
                "conf t",
                "interface {$onuPort}",
                "shutdown",
                "exit",
                "write",
            ];

            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000);
                $this->telnet->read(['/ZXAN#/i', '/(config)#/i', '/(config-if)#/i']);
            }

            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT ONU Suspend Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resume an ONU (enable traffic)
     */
    public function resumeOnu($shelf, $slot, $port, $onuId)
    {
        try {
            if (!$this->connect()) return false;

            $onuPort = "gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}";
            Log::info("Resuming ONU {$onuPort}");

            $commands = [
                "conf t",
                "interface {$onuPort}",
                "no shutdown",
                "exit",
                "write",
            ];

            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000);
                $this->telnet->read(['/ZXAN#/i', '/(config)#/i', '/(config-if)#/i']);
            }

            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT ONU Resume Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update ONU bandwidth profile
     */
    public function updateOnuProfile($shelf, $slot, $port, $onuId, $package)
    {
        try {
            if (!$this->connect()) return false;

            $onuPort = "gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}";
            $profileName = is_string($package) ? $package : ($package->bandwidth_profile ?? 'UP-100M');
            Log::info("Updating profile for ONU {$onuPort} to {$profileName}");

            $commands = [
                "conf t",
                "interface {$onuPort}",
                "tcont 1 profile {$profileName}",
                "exit",
                "write",
            ];

            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000);
                $this->telnet->read(['/ZXAN#/i', '/(config)#/i', '/(config-if)#/i']);
            }

            $this->telnet->disconnect();
            return true;
        } catch (\Exception $e) {
            Log::error("OLT ONU Profile Update Error: " . $e->getMessage());
            return false;
        }
    }
}
