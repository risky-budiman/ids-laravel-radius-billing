<?php

namespace App\Observers;

use App\Models\Radius\Nas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class NasObserver
{
    /**
     * Handle the Nas "saved" event.
     * Triggered after create or update
     */
    public function saved(Nas $nas): void
    {
        $this->reloadRadius();
    }

    /**
     * Handle the Nas "deleted" event.
     */
    public function deleted(Nas $nas): void
    {
        $this->reloadRadius();
    }

    /**
     * Send SIGHUP to FreeRADIUS to reload clients from SQL
     */
    protected function reloadRadius(): void
    {
        try {
            // We use 'sudo systemctl reload freeradius'
            // Ensure 'www-data' has sudo permission for this specific command without password
            $result = Process::run('sudo systemctl reload freeradius');
            
            if ($result->successful()) {
                Log::info("FreeRADIUS reloaded successfully after NAS change.");
            } else {
                Log::error("Failed to reload FreeRADIUS: " . $result->errorOutput());
            }
        } catch (\Exception $e) {
            Log::error("Error triggering FreeRADIUS reload: " . $e->getMessage());
        }
    }
}
