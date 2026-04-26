<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsappMessageJob;
use App\Models\Customer;
use App\Models\Region;
use App\Models\Stb;
use App\Models\Sto;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappBroadcastController extends Controller
{
    public function create()
    {
        $regions = Region::orderBy('code')->get();
        $stos = Sto::orderBy('code')->get();
        $stbs = Stb::orderBy('code')->get();
        
        // Hanya ambil template yang aktif
        $templates = WhatsappTemplate::where('is_active', true)->orderBy('name')->get();
        
        return view('whatsapp-broadcast.create', compact('regions', 'stos', 'stbs', 'templates'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:whatsapp_templates,id',
            'region_code' => 'nullable|string',
            'sto_code' => 'nullable|string',
            'stb_code' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $query = Customer::query();

        if ($request->filled('region_code')) {
            $query->where('region_code', $request->region_code);
        }

        if ($request->filled('sto_code')) {
            $query->where('sto_code', $request->sto_code);
        }

        if ($request->filled('stb_code')) {
            $query->where('stb_code', $request->stb_code);
        }

        if ($request->filled('status')) {
            if ($request->status === 'unpaid') {
                $query->whereHas('invoices', function ($q) {
                    $q->where('status', 'unpaid');
                });
            } elseif ($request->status === 'paid') {
                $query->whereDoesntHave('invoices', function ($q) {
                    $q->where('status', 'unpaid');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        // Jangan kirim ke pelanggan yang tidak punya nomor telepon
        $query->whereNotNull('phone')->where('phone', '!=', '');

        $customerIds = $query->pluck('id')->toArray();

        if (empty($customerIds)) {
            return back()->with('error', 'Tidak ada pelanggan yang cocok dengan kriteria filter tersebut atau mereka tidak memiliki nomor telepon.');
        }

        $templateId = $request->template_id;
        $customDate = $request->custom_date;
        $template = WhatsappTemplate::find($templateId);

        // Dispatch Jobs
        foreach ($customerIds as $customerId) {
            $customer = Customer::find($customerId);
            if ($customer && $customer->phone) {
                // Pre-create log for 'waiting' status tracking
                $log = \App\Models\WhatsappLog::create([
                    'target_phone' => $customer->phone,
                    'message' => 'Processing template: ' . $template->name, // Will be updated by service
                    'status' => 'pending'
                ]);

                SendWhatsappMessageJob::dispatch($customerId, $templateId, $customDate, $log->id);
            }
        }

        Log::info("Broadcast WA: Dispatched " . count($customerIds) . " messages using Template ID {$templateId}");

        return redirect()->route('whatsapp-broadcast.create')
            ->with('success', count($customerIds) . ' pesan Broadcast telah dimasukkan ke dalam antrean (Queue) dan akan segera dikirim secara bertahap di latar belakang.');
    }
}
