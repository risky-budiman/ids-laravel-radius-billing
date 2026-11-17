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

        $retention = app(\App\Services\LogRetention::class);
        $retentionPlan = $retention->plan();

        return view('activity-logs.index', compact('activities', 'users', 'retentionPlan'));
    }

    /** Simpan masa simpan log. "Pratinjau" hanya menghitung yang akan dihapus dengan pilihan baru. */
    public function retention(Request $request, \App\Services\LogRetention $retention)
    {
        $rules = [];
        foreach (\App\Services\LogRetention::OPTIONS as $key => $allowed) {
            $rules[$key] = 'required|integer|in:' . implode(',', $allowed);
        }
        $data = $request->validate($rules);

        if ($request->input('action') === 'preview') {
            $total = collect($retention->plan($data))->sum('count');

            return back()->withInput()->with('retention_preview', $retention->plan($data))
                ->with('success', "Pratinjau: {$total} baris akan dihapus pada penghapusan harian berikutnya (03:45) bila disimpan.");
        }
        \App\Services\LogRetention::set($data);
        $total = collect($retention->plan())->sum('count');
        \App\Models\ActivityLog::log('logs_retention_updated', 'Masa simpan log diubah: ' . collect($data)->map(fn ($v, $k) => str_replace('log_retention_', '', $k) . '=' . ($v ?: 'selamanya'))->implode(', ') . '.');

        return back()->with('success', $total
            ? "Masa simpan disimpan. {$total} baris lama akan dihapus pada penghapusan harian berikutnya (03:45)."
            : 'Masa simpan disimpan. Saat ini tidak ada log yang melewati batas.');
    }
}
