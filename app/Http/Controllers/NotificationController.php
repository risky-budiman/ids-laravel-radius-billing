<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->unreadNotifications;
        
        // Group by type (shortened name of notification class)
        $grouped = $notifications->groupBy(function($notif) {
            $class = explode('\\', $notif->type);
            $className = end($class);
            
            if (str_contains($className, 'Ticket')) return 'Tickets';
            if (str_contains($className, 'Invoice')) return 'Billing';
            if (str_contains($className, 'Inventory')) return 'Inventory';
            
            return 'General';
        });

        return response()->json($grouped);
    }

    public function markAsRead(Request $request)
    {
        if ($request->id) {
            Auth::user()->unreadNotifications->where('id', $request->id)->markAsRead();
        } else {
            Auth::user()->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
