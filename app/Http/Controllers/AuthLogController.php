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

        // Attach reason details to each log
        foreach ($logs as $log) {
            $log->reason = $this->determineReason($log);
        }

        // Stats
        $totalToday = RadPostAuth::whereDate('authdate', today())->count();
        $successToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Accept')->count();
        $failedToday = RadPostAuth::whereDate('authdate', today())->where('reply', 'Access-Reject')->count();

        return view('auth-logs.index', compact('logs', 'totalToday', 'successToday', 'failedToday'));
    }

    /**
     * Hapus satu entri log
     */
    public function destroy($id)
    {
        $log = RadPostAuth::findOrFail($id);
        $log->delete();

        return redirect()->back()->with('success', 'Log berhasil dihapus.');
    }

    /**
     * Bersihkan semua log
     */
    public function clear()
    {
        RadPostAuth::truncate();

        return redirect()->back()->with('success', 'Semua log berhasil dibersihkan.');
    }

    /**
     * Helper to determine why a login was accepted or rejected
     */
    private function determineReason($log)
    {
        if ($log->reply === 'Access-Accept') {
            return 'Authentication Successful';
        }

        // For Rejects, check the database
        $customer = \App\Models\Customer::where('username', $log->username)->first();
        
        if (!$customer) {
            return 'User Not Found in Billing';
        }

        if (!$customer->is_active) {
            return 'Account Suspended / Inactive';
        }

        // If user is active but rejected, check password
        $radCheck = \App\Models\Radius\RadCheck::where('username', $log->username)
            ->where('attribute', 'Cleartext-Password')
            ->first();

        if ($radCheck && $log->pass !== $radCheck->value) {
            return 'Wrong Password Attempted';
        }

        return 'Rejected by RADIUS (Check NAS/Policy)';
    }
}
