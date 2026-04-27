<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filter by User
        if ($request->filled('user_id')) {
            if ($request->user_id === 'system') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_id', $request->user_id);
            }
        }

        // Filter by Action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by Date Range
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        // Search in Description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $activities = $query->paginate(50)->withQueryString();
        $users = \App\Models\User::orderBy('name')->get();

        return view('activity-logs.index', compact('activities', 'users'));
    }
}
