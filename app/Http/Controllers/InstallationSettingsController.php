<?php

namespace App\Http\Controllers;

use App\Services\InstallationService;
use Illuminate\Http\Request;

/**
 * Pengaturan Instalasi (administrator): BA & tanda tangan, invoice instalasi, batas bayar, pengingat.
 */
class InstallationSettingsController extends Controller
{
    public function __construct(private InstallationService $installation)
    {
    }

    public function index()
    {
        return view('settings.installation', [
            'values' => collect(InstallationService::SETTINGS)->map(fn ($d, $k) => $this->installation->setting($k))->all(),
            'defaultTerms' => InstallationService::DEFAULT_TERMS,
            'recent' => \App\Models\InstallationReport::with('customer:id,name,customer_code')->latest()->limit(15)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'activation_ba_enabled' => 'nullable|boolean',
            'installation_invoice_enabled' => 'nullable|boolean',
            'installation_grace_hours' => 'required|integer|min:0|max:720',
            'installation_reminder_hours' => 'required|integer|min:0|max:48',
            'installation_ba_terms' => 'nullable|string|max:5000',
        ], [], [
            'installation_grace_hours' => 'batas bayar', 'installation_reminder_hours' => 'pengingat', 'installation_ba_terms' => 'klausul BA',
        ]);

        $data['activation_ba_enabled'] = $request->boolean('activation_ba_enabled') ? '1' : '0';
        $data['installation_invoice_enabled'] = $request->boolean('installation_invoice_enabled') ? '1' : '0';
        $data['installation_ba_terms'] = trim((string) ($data['installation_ba_terms'] ?? '')) ?: InstallationService::DEFAULT_TERMS;
        $this->installation->saveSettings($data);

        return redirect()->route('settings.installation')->with('success', 'Pengaturan instalasi disimpan.');
    }
}
