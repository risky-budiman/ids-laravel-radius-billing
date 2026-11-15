<?php

namespace App\Http\Controllers;

use App\Models\InstallationReport;
use App\Services\InstallationService;
use Illuminate\Http\Request;

/**
 * Berita Acara (BA) Instalasi — halaman staf (web) dan halaman publik pelanggan (tautan WhatsApp).
 */
class InstallationReportController extends Controller
{
    public function __construct(private InstallationService $installation)
    {
    }

    /** Staf: lihat, tanda tangani, kirim tautan, cetak. */
    public function show(InstallationReport $report)
    {
        return view('installation-reports.show', ['report' => $report->load('customer', 'technician'), 'public' => false]);
    }

    /** Staf: tanda tangan pelanggan (di perangkat teknisi) atau teknisi. */
    public function sign(Request $request, InstallationReport $report)
    {
        $data = $request->validate([
            'role' => 'required|in:customer,technician',
            'signature' => 'required|string|max:700000',
            'signer_name' => 'nullable|string|max:120',
            'latitude' => 'nullable|numeric', 'longitude' => 'nullable|numeric',
        ]);

        try {
            $this->installation->sign($report, $data['role'], $data['signature'], $data['signer_name'] ?? null, [
                'by_user' => $request->user()?->id, 'ip' => $request->ip(), 'via' => 'staf',
                'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'at' => now()->toIso8601String(),
            ]);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tanda tangan ' . ($data['role'] === 'customer' ? 'pelanggan' : 'teknisi') . ' tersimpan.');
    }

    /** Staf: kirim ulang tautan BA ke WhatsApp pelanggan. */
    public function sendLink(InstallationReport $report)
    {
        $customer = $report->customer;
        if (! $customer?->phone) {
            return back()->with('error', 'Pelanggan tidak memiliki nomor WhatsApp.');
        }
        \App\Jobs\SendCustomWhatsappMessageJob::dispatch($customer->phone,
            "Halo *{$customer->name}*,\n\nMohon periksa & tanda tangani Berita Acara Instalasi *{$report->number}*:\n{$report->signUrl()}\n\nTerima kasih.");

        return back()->with('success', 'Tautan BA dikirim ke WhatsApp pelanggan.');
    }

    /** Publik (pelanggan, lewat token): lihat & tanda tangan. */
    public function publicShow(string $token)
    {
        $report = InstallationReport::with('customer', 'technician')->where('sign_token', $token)->firstOrFail();

        return response()->view('installation-reports.public', ['report' => $report, 'public' => true])
            ->header('X-Robots-Tag', 'noindex');
    }

    public function publicSign(Request $request, string $token)
    {
        $report = InstallationReport::where('sign_token', $token)->firstOrFail();
        $data = $request->validate([
            'signature' => 'required|string|max:700000',
            'signer_name' => 'required|string|max:120',
            'agree' => 'accepted',
        ], [], ['signer_name' => 'nama penanda tangan', 'agree' => 'persetujuan']);

        try {
            $this->installation->sign($report, 'customer', $data['signature'], $data['signer_name'], [
                'ip' => $request->ip(), 'via' => 'tautan', 'user_agent' => substr((string) $request->userAgent(), 0, 200), 'at' => now()->toIso8601String(),
            ]);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('installation-report.public', $token)->with('success', 'Terima kasih, Berita Acara sudah ditandatangani.');
    }
}
