<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class LogAuthenticationActivity
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            if ($event instanceof Login && $event->user) {
                $this->logAction($event->user, 'login');
            } elseif ($event instanceof Logout && $event->user) {
                $this->logAction($event->user, 'logout');
            }
        } catch (\Throwable $e) {
            Log::error("Failed to record authentication activity log: " . $e->getMessage());
        }
    }

    protected function logAction($user, $action)
    {
        $isCustomer = $user instanceof Customer;
        $isUser = $user instanceof User;

        if ($isCustomer) {
            $userId = null; // Customer is not in the users table, avoid foreign key constraint error
            $description = $action === 'login' 
                ? "Pelanggan {$user->name} ({$user->username}) masuk ke Portal Pelanggan" 
                : "Pelanggan {$user->name} ({$user->username}) keluar dari Portal Pelanggan";
        } else {
            $userId = $user->id;
            $description = $action === 'login'
                ? "User {$user->name} logged into the system"
                : "User {$user->name} logged out of the system";
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'properties' => [
                'user_agent' => Request::userAgent(),
                'guard' => $isCustomer ? 'customer' : 'web',
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
