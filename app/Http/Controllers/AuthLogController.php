<?php

namespace App\Http\Controllers;

use App\Models\Radius\RadPostAuth;
use Illuminate\Http\Request;

class AuthLogController extends Controller
{
    public function index(Request $request)
    {
        $query = RadPostAuth::query()->orderBy('authdate', 'desc');

        // Filter by username
        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }

        // Filter by reply status
        if ($request->filled('status')) {
            if ($request->status === 'accept') {
                $query->where('reply', 'Access-Accept');
            } elseif ($request->status === 'reject') {
                $query->where('reply', 'Access-Reject');
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('authdate', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('authdate', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->appends($request->query());

        // Stats
        $totalToday = RadPostAuth::whereDate('authdate', today())->count();
        $successToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Accept')->count();
        $failedToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Reject')->count();

        return view('auth-logs.index', compact('logs', 'totalToday', 'successToday', 'failedToday'));
    }
}
