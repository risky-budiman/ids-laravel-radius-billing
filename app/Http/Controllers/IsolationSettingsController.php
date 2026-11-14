<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerIsolation;
use App\Services\CustomerLifecycleService;
use App\Services\IsolationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menu Pengaturan Isolir (administrator): alokasi IP isolir via RADIUS.
 */
class IsolationSettingsController extends Controller
{
    public function __construct(private IsolationService $isolation)
    {
    }

    public function index()
    {
        return view('settings.isolation', [
            'cfg' => $this->isolation->config(),
            'capacity' => $this->isolation->capacity(),
            'isolations' => CustomerIsolation::with('customer:id,name,customer_code,username,status')->latest('isolated_at')->limit(200)->get(),
            'suspendedCount' => Customer::where('status', Customer::STATUS_SUSPENDED)->count(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'isolir_enabled' => 'nullable|boolean',
            'isolir_mode' => ['required', Rule::in(['range', 'pool'])],
            'isolir_group' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_ip_start' => 'nullable|required_if:isolir_mode,range|ipv4',
            'isolir_ip_end' => 'nullable|required_if:isolir_mode,range|ipv4',
            'isolir_pool_name' => ['nullable', 'required_if:isolir_mode,pool', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_rate_limit' => ['nullable', 'string', 'max:64', 'regex:/^[0-9A-Za-z\/ .]+$/'],
            'isolir_address_list' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_mikrotik_group' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_page_host' => 'nullable|string|max:255',
            'disconnect_now' => 'nullable|boolean',
        ], [
            'isolir_ip_start.required_if' => 'IP awal wajib diisi untuk mode rentang IP.',
            'isolir_ip_end.required_if' => 'IP akhir wajib diisi untuk mode rentang IP.',
            'isolir_pool_name.required_if' => 'Nama pool wajib diisi untuk mode pool MikroTik.',
            'isolir_group.regex' => 'Nama grup hanya boleh huruf, angka, titik, garis bawah, dan tanda minus.',
        ]);

        if ($data['isolir_mode'] === 'range') {
            $start = ip2long($data['isolir_ip_start']);
            $end = ip2long($data['isolir_ip_end']);
            if ($end < $start) {
                return back()->withInput()->withErrors(['isolir_ip_end' => 'IP akhir harus sama atau setelah IP awal.']);
            }
            if ($end - $start > 65535) {
                return back()->withInput()->withErrors(['isolir_ip_end' => 'Rentang terlalu besar (maksimal 65.536 alamat).']);
            }
        }

        $data['isolir_enabled'] = $request->boolean('isolir_enabled') ? '1' : '0';
        $stats = $this->isolation->saveSettings($data);

        $disconnected = 0;
        if ($request->boolean('disconnect_now')) {
            $lifecycle = app(CustomerLifecycleService::class);
            $usernames = $data['isolir_enabled'] === '1'
                ? Customer::where('status', Customer::STATUS_SUSPENDED)->pluck('username')
                : collect();
            foreach ($usernames->filter() as $username) {
                $lifecycle->disconnectSessions($username);
                $disconnected++;
            }
        }

        $msg = $data['isolir_enabled'] === '1'
            ? "Pengaturan isolir disimpan. {$stats['isolated']} pelanggan terisolir diterapkan ulang"
              . ($stats['without_ip'] ? " ({$stats['without_ip']} tanpa IP: rentang penuh/kosong)" : '')
              . ($disconnected ? ", {$disconnected} sesi diputus agar langsung memakai IP isolir." : '. Berlaku saat pelanggan tersambung ulang.')
            : "Isolir RADIUS dimatikan. {$stats['released']} pelanggan dikembalikan ke grup paketnya.";

        return redirect()->route('settings.isolation')->with('success', $msg);
    }
}
