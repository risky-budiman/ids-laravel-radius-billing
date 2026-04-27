<?php

namespace App\Services\Network;

use phpseclib3\Net\Telnet;
use Exception;
use Illuminate\Support\Facades\Log;

class ZteOltProvisioningService
{
    protected $telnet;
    protected $olt;

    public function __construct($olt)
    {
        $this->olt = $olt;
    }

    /**
     * Connect to OLT via Telnet
     */
    protected function connect()
    {
        try {
            $this->telnet = new Telnet($this->olt->ip_address, $this->olt->telnet_port);
            $this->telnet->login($this->olt->username, $this->olt->password);
            
            // Wait for prompt and enter enable mode if needed
            // This part depends on OLT configuration (enable password etc)
            $this->telnet->write("enable\n");
            $this->telnet->read("Password:");
            $this->telnet->write($this->olt->password . "\n"); // Assuming same password for enable
            
            return true;
        } catch (Exception $e) {
            Log::error("OLT Telnet Connection Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Basic Provisioning Script for ZTE
     */
    public function registerOnu($shelf, $slot, $port, $onuId, $sn, $type, $vlan, $package)
    {
        if (!$this->connect()) return false;

        try {
            $commands = [
                "conf t",
                "interface gpon-olt_{$shelf}/{$slot}/{$port}",
                "onu {$onuId} type {$type} sn {$sn}",
                "exit",
                "interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}",
                "tcont 1 name T1 profile DBA-{$package}", // Assumes DBA profile exists
                "gemport 1 name G1 tcont 1",
                "exit",
                "pon-onu-mng gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}",
                "service HSI gemport 1 vlan {$vlan}",
                "exit",
                "interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}",
                "vlan port eth_0/1 mode tag vlan {$vlan}",
                "exit"
            ];

            foreach ($commands as $cmd) {
                $this->telnet->write($cmd . "\n");
                usleep(200000); // Wait 200ms
            }

            $this->telnet->write("exit\n");
            return true;
        } catch (Exception $e) {
            Log::error("Provisioning failed: " . $e->getMessage());
            return false;
        } finally {
            $this->telnet->disconnect();
        }
    }

    /**
     * Create DBA Profile (ZTE)
     */
    public function createDbaProfile($name, $bandwidth)
    {
        if (!$this->connect()) return false;
        
        $this->telnet->write("conf t\n");
        $this->telnet->write("pon\n");
        $this->telnet->write("onu-profile dba {$name} type 3 bandwidth {$bandwidth}\n");
        $this->telnet->write("exit\n");
        $this->telnet->write("exit\n");
        $this->telnet->disconnect();
        
        return true;
    }

    /**
     * Deprovision/Delete ONU from OLT
     */
    public function deleteOnu($shelf, $slot, $port, $onuId)
    {
        if (!$this->connect()) return false;

        try {
            $this->telnet->write("conf t\n");
            $this->telnet->write("interface gpon-olt_{$shelf}/{$slot}/{$port}\n");
            $this->telnet->write("no onu {$onuId}\n");
            $this->telnet->write("exit\n");
            $this->telnet->write("exit\n");
            return true;
        } catch (Exception $e) {
            Log::error("OLT Delete ONU failed: " . $e->getMessage());
            return false;
        } finally {
            $this->telnet->disconnect();
        }
    }

    /**
     * Suspend/Isolir ONU (Block Traffic)
     * Note: For ZTE, we can disable the ONU admin state.
     */
    public function suspendOnu($shelf, $slot, $port, $onuId)
    {
        if (!$this->connect()) return false;

        try {
            $this->telnet->write("conf t\n");
            $this->telnet->write("interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}\n");
            $this->telnet->write("admin state disable\n"); // Disable ONU
            $this->telnet->write("exit\n");
            $this->telnet->write("exit\n");
            return true;
        } catch (Exception $e) {
            Log::error("OLT Suspend ONU failed: " . $e->getMessage());
            return false;
        } finally {
            $this->telnet->disconnect();
        }
    }

    /**
     * Resume ONU (Unblock Traffic)
     */
    public function resumeOnu($shelf, $slot, $port, $onuId)
    {
        if (!$this->connect()) return false;

        try {
            $this->telnet->write("conf t\n");
            $this->telnet->write("interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}\n");
            $this->telnet->write("admin state enable\n"); // Enable ONU
            $this->telnet->write("exit\n");
            $this->telnet->write("exit\n");
            return true;
        } catch (Exception $e) {
            Log::error("OLT Resume ONU failed: " . $e->getMessage());
            return false;
        } finally {
            $this->telnet->disconnect();
        }
    }

    /**
     * Update ONU DBA Profile (Speed Update)
     */
    public function updateOnuProfile($shelf, $slot, $port, $onuId, $package)
    {
        if (!$this->connect()) return false;

        try {
            $this->telnet->write("conf t\n");
            $this->telnet->write("interface gpon-onu_{$shelf}/{$slot}/{$port}:{$onuId}\n");
            // Delete old tcont first or just reassign if supported
            // On ZTE, usually you have to remove it and recreate it, or just overwrite it
            // Assuming overwrite is supported:
            $this->telnet->write("tcont 1 name T1 profile DBA-{$package}\n");
            $this->telnet->write("exit\n");
            $this->telnet->write("exit\n");
            return true;
        } catch (Exception $e) {
            Log::error("OLT Update Profile failed: " . $e->getMessage());
            return false;
        } finally {
            $this->telnet->disconnect();
        }
    }
}
