<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Fetch latest 10 unread and 10 read notifications
        $unread = $user->unreadNotifications()->latest()->take(10)->get();
        $read = $user->notifications()->whereNotNull('read_at')->latest()->take(10)->get();
        
        // Filter for Kasir: Only Billing/Invoice notifications
        if ($user->isKasir()) {
            $unread = $unread->filter(function($notif) {
                return str_contains($notif->type, 'Invoice');
            });
            $read = $read->filter(function($notif) {
                return str_contains($notif->type, 'Invoice');
            });
        }

        // Merge and sort by created_at desc within their respective scopes, then group
        $all = $unread->concat($read);

        $grouped = $all->groupBy(function($notif) {
            return $notif->read_at === null ? 'Belum Dibaca' : 'Sudah Dibaca';
        });

        // Ensure keys order: 'Belum Dibaca' first, then 'Sudah Dibaca'
        $orderedGrouped = collect([]);
        if ($grouped->has('Belum Dibaca')) {
            $orderedGrouped->put('Belum Dibaca', $grouped->get('Belum Dibaca'));
        }
        if ($grouped->has('Sudah Dibaca')) {
            $orderedGrouped->put('Sudah Dibaca', $grouped->get('Sudah Dibaca'));
        }

        return response()->json($orderedGrouped);
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
