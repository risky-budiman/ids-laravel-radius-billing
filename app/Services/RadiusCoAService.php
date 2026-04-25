<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class RadiusCoAService
{
    /**
     * Send a Disconnect-Request to the NAS to terminate a user's session.
     * Requires the 'radclient' binary to be installed on the host OS.
     *
     * @param string $nasIp NAS IP Address
     * @param string $secret NAS Secret 
     * @param string $username Radius User-Name
     * @return bool True if successfully disconnected
     */
    public function disconnect(string $nasIp, string $secret, string $username): bool
    {
        try {
            // Added -t 2 (timeout 2s) to prevent hanging if NAS doesn't respond
            $command = sprintf(
                'echo "User-Name=\"%s\"" | radclient -t 2 -x %s:3799 disconnect "%s" 2>&1',
                $username,
                $nasIp,
                $secret
            );

            // Execute the shell command
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                Log::info("CoA Disconnect sent successfully for {$username} at NAS {$nasIp}");
                return true;
            } else {
                $errorMsg = implode("\n", $output);
                Log::error("CoA Disconnect failed for {$username} at {$nasIp}. Exit Code: {$returnVar}. Output: {$errorMsg}");
                return false;
            }
        } catch (Exception $e) {
            Log::error("Exception in CoA Disconnect for {$username}: " . $e->getMessage());
            return false;
        }
    }
}
