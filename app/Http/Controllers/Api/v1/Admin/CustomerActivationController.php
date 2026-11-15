<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Services\CustomerActivationService;
use App\Services\CustomerLifecycleService;
use App\Services\InstallationService;
use Illuminate\Http\Request;

/**
 * Wizard aktivasi & dismantle untuk aplikasi mobile teknisi.
 *
 *   GET  /api/v1/admin/customers/{id}/activation        pilihan form (modem, material, OLT, ODC/ODP, rekening)
 *   POST /api/v1/admin/customers/{id}/activate          aktivasi (multipart bila mengirim foto)
 *   GET  /api/v1/admin/customers/{id}/dismantle         perangkat terpasang di pelanggan
 *   POST /api/v1/admin/customers/{id}/dismantle         tarik perangkat & cabut layanan
 *   POST /api/v1/admin/customers/{id}/request-dismantle  ajukan dismantle (membuat tiket)
 *   GET  /api/v1/admin/customers/{id}/installation-report  BA instalasi terakhir pelanggan
 *   POST /api/v1/admin/installation-reports/{id}/sign    tanda tangan BA (pelanggan / teknisi)
 *
 * Hak akses: administrator, admin, teknisi (rute). Aturan & efeknya sama persis dengan wizard web.
 */
class CustomerActivationController extends Controller
{
    public function __construct(private CustomerActivationService $wizard, private InstallationService $installation)
    {
    }

    public function activationForm(int $id)
    {
        $customer = Customer::with('package:id,name,price')->findOrFail($id);
        $o = $this->wizard->activationOptions();

        return response()->json([
            'customer' => $this->customerPayload($customer),
            'can_activate' => in_array($customer->status, CustomerActivationService::ACTIVATABLE, true),
            'modems' => $o['serial_items']->flatMap(fn ($item) => $item->stocks->map(fn ($s) => [
                'stock_id' => $s->id, 'item' => $item->name, 'serial_number' => $s->serial_number,
                'mac_address' => $s->mac_address, 'status' => $s->status, 'condition' => $s->condition,
            ]))->values(),
            'consumables' => $o['consumable_items']->map(fn ($i) => ['item_id' => $i->id, 'name' => $i->name, 'unit' => $i->unit])->values(),
            'olts' => $o['olts']->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values(),
            'odcs' => $o['odcs']->map(fn ($x) => ['id' => $x->id, 'name' => $x->name ?? $x->code ?? (string) $x->id])->values(),
            'odps' => $o['odps']->map(fn ($x) => ['id' => $x->id, 'name' => $x->name ?? $x->code ?? (string) $x->id, 'odc_id' => $x->odc_id ?? null])->values(),
            'bank_accounts' => $o['bank_accounts'],
            'payment_methods' => ['cash', 'transfer', 'pg'],
        ]);
    }

    public function activate(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validate(CustomerActivationService::activationRules());

        try {
            $result = $this->wizard->activate($customer, $data, $request->user(),
                collect(CustomerActivationService::PHOTOS)->mapWithKeys(fn ($p) => [$p => $request->file($p)])->all());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $inv = $result['installation_invoice'];
        $unpaid = $inv && $inv->status !== 'paid';
        $grace = $result['customer']->activation_grace_expires_at;

        return response()->json([
            'message' => 'Pelanggan berhasil diaktivasi.' . ($unpaid
                ? ' Invoice instalasi belum dibayar' . ($grace ? ' (batas ' . $grace->format('d/m/Y H:i') . ').' : '.') : ''),
            'customer' => $this->customerPayload($result['customer']),
            'installation_invoice' => $inv ? [
                'id' => $inv->id, 'invoice_number' => $inv->invoice_number, 'amount' => (float) $inv->amount,
                'status' => $inv->status, 'payment_link' => route('portal.pay', $inv->invoice_number),
                'grace_expires_at' => $grace?->toIso8601String(),
            ] : null,
            'installation_report' => $result['installation_report'] ? $this->reportPayload($result['installation_report']) : null,
        ]);
    }

    /** BA instalasi terakhir milik pelanggan (404 bila belum ada). */
    public function installationReport(int $id)
    {
        $report = InstallationReport::where('customer_id', $id)->latest('id')->firstOrFail();

        return response()->json(['installation_report' => $this->reportPayload($report, true)]);
    }

    /** Tanda tangan BA dari aplikasi: role customer (di HP teknisi) atau technician; signature = PNG data-URL. */
    public function signInstallationReport(Request $request, int $id)
    {
        $report = InstallationReport::findOrFail($id);
        $data = $request->validate([
            'role' => 'required|in:customer,technician',
            'signature' => 'required|string|max:700000',
            'signer_name' => 'nullable|string|max:120',
            'latitude' => 'nullable|numeric', 'longitude' => 'nullable|numeric',
        ]);

        try {
            $report = $this->installation->sign($report, $data['role'], $data['signature'], $data['signer_name'] ?? null, [
                'by_user' => $request->user()?->id, 'ip' => $request->ip(), 'via' => 'api',
                'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null, 'at' => now()->toIso8601String(),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Tanda tangan tersimpan.', 'installation_report' => $this->reportPayload($report)]);
    }

    private function reportPayload(InstallationReport $r, bool $withSnapshot = false): array
    {
        $out = [
            'id' => $r->id, 'number' => $r->number, 'sign_url' => $r->signUrl(),
            'customer_signed_at' => $r->customer_signed_at?->toIso8601String(),
            'customer_signer_name' => $r->customer_signer_name,
            'technician_signed_at' => $r->technician_signed_at?->toIso8601String(),
            'fully_signed' => $r->isFullySigned(),
            'created_at' => $r->created_at?->toIso8601String(),
        ];
        if ($withSnapshot) {
            $out['snapshot'] = $r->snapshot;
            $out['terms'] = $r->terms;
        }

        return $out;
    }

    public function dismantleForm(int $id)
    {
        $customer = Customer::findOrFail($id);

        return response()->json([
            'customer' => $this->customerPayload($customer),
            'can_dismantle' => ! in_array($customer->status, [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED], true),
            'installed_equipment' => $this->wizard->installedEquipment($customer)->map(fn ($s) => [
                'stock_id' => $s->id, 'item' => $s->item?->name, 'serial_number' => $s->serial_number, 'mac_address' => $s->mac_address,
            ])->values(),
        ]);
    }

    public function dismantle(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validate([
            'stock_ids' => 'nullable|array',
            'stock_ids.*' => 'integer|exists:inventory_stocks,id',
            'broken_stock_ids' => 'nullable|array',   // bagian dari stock_ids yang rusak
            'broken_stock_ids.*' => 'integer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $customer = $this->wizard->dismantle($customer, array_map('intval', $data['stock_ids'] ?? []), $request->user(), $data['notes'] ?? null, $data['broken_stock_ids'] ?? []);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Dismantle selesai. Perangkat kembali ke gudang.', 'customer' => $this->customerPayload($customer)]);
    }

    public function requestDismantle(int $id, CustomerLifecycleService $lifecycle)
    {
        $customer = Customer::findOrFail($id);
        if (! $lifecycle->requestDismantle($customer)) {
            return response()->json(['message' => 'Pelanggan ini sudah dalam proses / selesai dismantle.'], 422);
        }

        $ticket = \App\Models\Ticket::where('customer_id', $customer->id)->where('type', 'dismantle')->latest('id')->first();

        return response()->json(['message' => 'Dismantle diajukan.', 'ticket_id' => $ticket?->id, 'ticket_number' => $ticket?->ticket_number]);
    }

    private function customerPayload(Customer $c): array
    {
        return [
            'id' => $c->id, 'customer_code' => $c->customer_code, 'name' => $c->name, 'username' => $c->username,
            'phone' => $c->phone, 'address' => $c->address, 'status' => $c->status, 'is_active' => (bool) $c->is_active,
            'package' => $c->package?->name, 'installation_fee' => (float) $c->installation_fee,
            'activated_at' => $c->activated_at?->toIso8601String(), 'onu_sn' => $c->onu_sn,
        ];
    }
}
