<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class RadiusCoAService
{
    /**
     * Send a Disconnect-Request to the NAS to terminate a user's session.
     * Requires the 'radclient' binary to be installed on the host OS.
     *
     * radclient dijalankan sebagai array argumen (tanpa shell) dan atribut dikirim
     * lewat stdin, sehingga username/session-id/secret berisi karakter khusus
     * tidak dapat menyisipkan perintah shell.
     *
     * @param string $nasIp NAS IP Address
     * @param string $secret NAS Secret
     * @param string $username Radius User-Name
     * @param string|null $sessionId Radius Acct-Session-Id (Optional but recommended)
     * @return bool True if successfully disconnected
     */
    public function disconnect(string $nasIp, string $secret, string $username, ?string $sessionId = null): bool
    {
        try {
            // Acct-Session-Id membuat permintaan lebih presisi.
            $attributes = 'User-Name = ' . $this->quote($username);
            if ($sessionId) {
                $attributes .= ', Acct-Session-Id = ' . $this->quote($sessionId);
            }

            // -t 2: timeout 2 detik agar tidak menggantung bila NAS tidak merespons.
            $result = Process::timeout(10)
                ->input($attributes . PHP_EOL)
                ->run(['radclient', '-t', '2', '-x', "{$nasIp}:3799", 'disconnect', $secret]);

            if ($result->successful()) {
                Log::info("CoA Disconnect sent successfully for {$username} at NAS {$nasIp}");
                return true;
            }

            $errorMsg = trim($result->output() . "\n" . $result->errorOutput());
            Log::error("CoA Disconnect failed for {$username} at {$nasIp}. Exit Code: {$result->exitCode()}. Output: {$errorMsg}");
            return false;
        } catch (Exception $e) {
            Log::error("Exception in CoA Disconnect for {$username}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kutip nilai atribut RADIUS untuk radclient.
     */
    protected function quote(string $value): string
    {
        return '"' . addcslashes($value, "\"\\") . '"';
    }
}
