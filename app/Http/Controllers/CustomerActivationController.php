<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerActivationService;
use App\Services\CustomerLifecycleService;
use Illuminate\Http\Request;

/**
 * Wizard aktivasi & dismantle (web). Logika di CustomerActivationService, dipakai juga API mobile.
 */
class CustomerActivationController extends Controller
{
    public function __construct(private CustomerActivationService $wizard)
    {
    }

    public function index(Customer $customer)
    {
        abort_if(auth()->user()->isSales(), 403, 'Sales tidak dapat melakukan aktivasi pelanggan.');

        $o = $this->wizard->activationOptions();

        return view('customers.activate', [
            'customer' => $customer,
            'serialItems' => $o['serial_items'],
            'consumableItems' => $o['consumable_items'],
            'olts' => $o['olts'],
            'regions' => $o['regions'],
            'stos' => $o['stos'],
            'stbs' => $o['stbs'],
            'odcs' => $o['odcs'],
            'odps' => $o['odps'],
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        abort_if(auth()->user()->isSales(), 403, 'Sales tidak dapat melakukan aktivasi pelanggan.');

        $data = $request->validate(CustomerActivationService::activationRules());

        try {
            $result = $this->wizard->activate($customer, $data, auth()->user(),
                collect(CustomerActivationService::PHOTOS)->mapWithKeys(fn ($p) => [$p => $request->file($p)])->all());
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $invoice = $result['installation_invoice'];
        $unpaid = $invoice && $invoice->status !== 'paid';

        // BA aktif -> lanjut ke tanda tangan pelanggan & teknisi
        if ($result['installation_report'] ?? null) {
            return redirect()->route('installation-reports.show', $result['installation_report'])
                ->with('success', 'Pelanggan diaktifkan. Silakan tanda tangani Berita Acara Instalasi.'
                    . ($unpaid ? ' Invoice instalasi belum dibayar.' : ''));
        }

        if ($unpaid && auth()->user()->hasRole(['administrator', 'admin', 'kasir'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Pelanggan diaktifkan. Invoice instalasi menunggu pembayaran.');
        }

        return redirect()->route('customers.index')->with('success', 'Pelanggan diaktifkan dan perangkat tercatat.');
    }

    public function dismantleForm(Customer $customer)
    {
        if (! auth()->user()->isAdmin() && ! auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Hanya teknisi atau admin yang dapat membuka form dismantle.');
        }

        return view('customers.dismantle', [
            'customer' => $customer,
            'installedEquipment' => $this->wizard->installedEquipment($customer),
        ]);
    }

    public function processDismantle(Request $request, Customer $customer)
    {
        if (! auth()->user()->isAdmin() && ! auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Hanya teknisi atau admin yang dapat memproses penarikan perangkat.');
        }

        $data = $request->validate([
            'stock_ids' => 'nullable|array',
            'stock_ids.*' => 'integer|exists:inventory_stocks,id',
            'broken_stock_ids' => 'nullable|array',
            'broken_stock_ids.*' => 'integer',
        ]);

        try {
            $this->wizard->dismantle($customer, array_map('intval', $data['stock_ids'] ?? []), auth()->user(), null, $data['broken_stock_ids'] ?? []);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Dismantle gagal: ' . $e->getMessage());

            return back()->with('error', 'Gagal memproses dismantle: ' . $e->getMessage());
        }

        return redirect()->route('customers.index')->with('success', 'Pelanggan dicabut dan perangkat kembali ke gudang.');
    }

    public function requestDismantle(Customer $customer, CustomerLifecycleService $lifecycle)
    {
        if (! auth()->user()->isAdmin() && ! auth()->user()->isTeknisi()) {
            return redirect()->route('customers.index')->with('error', 'Hanya teknisi atau admin yang dapat mengajukan dismantle.');
        }

        // Tiket penarikan dibuat oleh observer saat status berubah (sebelumnya juga dibuat di sini -> tiket ganda)
        if (! $lifecycle->requestDismantle($customer)) {
            return back()->with('error', 'Pelanggan ini sudah dalam proses / selesai dismantle.');
        }

        return back()->with('success', 'Permintaan dismantle telah diajukan. Tiket penarikan perangkat otomatis dibuat.');
    }
}
