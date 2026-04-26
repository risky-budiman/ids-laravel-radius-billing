<?php

namespace App\Http\Controllers;

use App\Models\WhatsappLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsappLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WhatsappLog::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('target_phone', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('whatsapp-logs.index', compact('logs'));
    }

    public function resend(WhatsappLog $whatsappLog, WhatsAppService $waService)
    {
        if ($whatsappLog->status !== 'failed') {
            return back()->with('error', 'Hanya pesan yang gagal (failed) yang bisa dikirim ulang.');
        }

        $whatsappLog->update(['status' => 'pending', 'error_reason' => null]);
        
        $waService->sendMessage($whatsappLog->target_phone, $whatsappLog->message, $whatsappLog->id);

        // Jika berhasil
        $whatsappLog->refresh();

        if ($whatsappLog->status === 'sent') {
            return back()->with('success', 'Pesan berhasil dikirim ulang!');
        } else {
            return back()->with('error', 'Kirim ulang gagal: ' . $whatsappLog->error_reason);
        }
    }
}
