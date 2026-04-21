<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class LogAuthenticationActivity
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof Login) {
            $this->logAction($event->user, 'login', 'User logged into the system');
        } elseif ($event instanceof Logout) {
            if ($event->user) {
                $this->logAction($event->user, 'logout', 'User logged out of the system');
            }
        }
    }

    protected function logAction($user, $action, $description)
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'properties' => [
                'user_agent' => Request::userAgent(),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
