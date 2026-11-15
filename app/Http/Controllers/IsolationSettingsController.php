<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerIsolation;
use App\Models\Radius\Nas;
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

    public function index(Request $request)
    {
        $nasList = Nas::orderBy('shortname')->get(['id', 'nasname', 'shortname', 'secret']);
        $nas = $nasList->firstWhere('id', (int) $request->query('nas')) ?? $nasList->first();

        return view('settings.isolation', [
            'cfg' => $this->isolation->config(),
            'capacity' => $this->isolation->capacity(),
            'isolations' => CustomerIsolation::with('customer:id,name,customer_code,username,status')->latest('isolated_at')->limit(200)->get(),
            'suspendedCount' => Customer::where('status', Customer::STATUS_SUSPENDED)->count(),
            'allowedHosts' => $this->isolation->allowedHosts(),
            'nasList' => $nasList,
            'nas' => $nas,
            'serverIp' => $this->isolation->serverIp(),
            'pagePort' => $this->isolation->pagePort(),
            'script' => $this->isolation->mikrotikScript($nas),
            'nginx' => $this->isolation->nginxSnippet(),
            'autoDismantle' => app(\App\Services\AutoDismantleService::class),
        ]);
    }

    /** Simpan pengaturan pengajuan dismantle otomatis. */
    public function updateDismantle(Request $request, \App\Services\AutoDismantleService $auto)
    {
        $data = $request->validate([
            'auto_dismantle_enabled' => 'nullable|boolean',
            'auto_dismantle_days' => 'required|integer|min:' . \App\Services\AutoDismantleService::MIN_DAYS . '|max:' . \App\Services\AutoDismantleService::MAX_DAYS,
        ], [], ['auto_dismantle_days' => 'batas hari']);

        $auto->saveSettings($request->boolean('auto_dismantle_enabled'), (int) $data['auto_dismantle_days']);
        $count = $auto->candidates()->count();

        return redirect()->route('settings.isolation')->with('success', $auto->enabled()
            ? "Dismantle otomatis aktif: pelanggan terisolir lebih dari {$auto->days()} hari diajukan setiap hari pukul 01:30 ({$count} pelanggan pada run berikutnya)."
            : 'Dismantle otomatis dinonaktifkan.');
    }

    /** Masa tenggang: jumlah hari setelah jatuh tempo sebelum pelanggan diisolir. */
    public function updateGrace(Request $request)
    {
        $data = $request->validate(['isolir_grace_days' => 'required|integer|min:0|max:' . \App\Models\Invoice::MAX_GRACE_DAYS], [], ['isolir_grace_days' => 'masa tenggang']);
        \App\Models\Setting::updateOrCreate(['key' => 'isolir_grace_days'], ['value' => (string) $data['isolir_grace_days']]);
        \Illuminate\Support\Facades\Cache::forget('app_settings');
        $days = (int) $data['isolir_grace_days'];

        return redirect()->route('settings.isolation')->with('success', $days
            ? "Masa tenggang {$days} hari: pelanggan diisolir bila tagihan belum lunas {$days} hari setelah jatuh tempo."
            : 'Tanpa masa tenggang: pelanggan diisolir sehari setelah jatuh tempo.');
    }

    /** Unduh skrip RouterOS (.rsc) untuk NAS terpilih, siap di-import: /import isolir.rsc */
    public function script(Request $request)
    {
        $nas = Nas::find((int) $request->query('nas'));
        $name = 'isolir-' . ($nas ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $nas->shortname ?: $nas->nasname) : 'mikrotik') . '.rsc';

        return response($this->isolation->mikrotikScript($nas), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'isolir_enabled' => 'nullable|boolean',
            'isolir_mode' => ['required', Rule::in(['range', 'pool'])],
            'isolir_group' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_ip_start' => 'nullable|required_if:isolir_mode,range|ipv4',
            'isolir_ip_end' => 'nullable|required_if:isolir_mode,range|ipv4',
            'isolir_pool_name' => ['nullable', 'required_if:isolir_mode,pool', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_rate_limit' => ['nullable', 'string', 'max:64', 'regex:/^[0-9A-Za-z\/ .]+$/'],
            'isolir_address_list' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_mikrotik_group' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'isolir_page_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+(:\d{1,5})?$/'],
            'isolir_allowed_hosts' => 'nullable|string|max:5000',
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

        $invalid = array_filter(IsolationService::parseHostList((string) ($data['isolir_allowed_hosts'] ?? '')),
            fn ($h) => ! IsolationService::isValidAllowEntry($h));
        if ($invalid) {
            return back()->withInput()->withErrors(['isolir_allowed_hosts' => 'Entri tidak valid: ' . implode(', ', $invalid)
                . '. Gunakan nama domain (mis. checkout.xendit.co), IP, atau CIDR; wildcard (*) tidak didukung MikroTik.']);
        }
        $data['isolir_allowed_hosts'] = implode("\n", IsolationService::parseHostList((string) ($data['isolir_allowed_hosts'] ?? '')));

        $data['isolir_group'] = ($data['isolir_group'] ?? '') ?: 'ISOLIR';   // grup internal FreeRADIUS, bukan profil MikroTik
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
