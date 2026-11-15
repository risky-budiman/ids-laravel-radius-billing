<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceWriteOffService;
use Illuminate\Http\Request;

/** Hapus buku piutang macet & pembatalannya (administrator, admin). */
class InvoiceWriteOffController extends Controller
{
    public function __construct(private InvoiceWriteOffService $service)
    {
    }

    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['reason' => 'required|string|max:500'], [], ['reason' => 'alasan']);
        try {
            $this->service->writeOff($invoice, $data['reason'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Piutang {$invoice->invoice_number} dihapus buku.");
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        try {
            $this->service->restore($invoice, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Penghapusan {$invoice->invoice_number} dibatalkan; invoice kembali belum dibayar.");
    }
}
