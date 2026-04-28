<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Subscribe the current user to push notifications.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required',
        ]);

        $user = Auth::user();
        
        // Use the customer if authenticated as customer, otherwise the user
        $subscribable = $user;
        if (method_exists($user, 'customer') && $user->customer) {
            // This depends on how auth is handled. In this project, 
            // customers might be a separate model or linked to a user.
        }

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'subscribable_id' => $user->id,
                'subscribable_type' => get_class($user),
                'public_key' => $request->keys['p256dh'],
                'auth_token' => $request->keys['auth'],
                'content_encoding' => $request->content_encoding ?? 'aesgcm',
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Unsubscribe the current user from push notifications.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        PushSubscription::where('endpoint', $request->endpoint)->delete();

        return response()->json(['success' => true]);
    }
}
