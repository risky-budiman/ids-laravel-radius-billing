<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wizard aktivasi & dismantle — satu jalur untuk web admin dan aplikasi mobile (API).
 *
 * Aktivasi : pelanggan baru/menunggu aktivasi -> aktif, data pemasangan & foto, modem & material
 *            keluar gudang, biaya instalasi (kas/transfer atau invoice gateway), tiket aktivasi selesai.
 * Dismantle: perangkat terpasang kembali ke gudang, status dicabut, tiket dismantle selesai,
 *            akun RADIUS dihapus, sesi diputus, ONU di-deprovision.
 */
class CustomerActivationService
{
    public const ACTIVATABLE = [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION];
    public const FIELDS = ['odc_id', 'odp_id', 'odp_port', 'vlan_id', 'static_ip', 'region_code', 'sto_code', 'stb_code',
        'latitude', 'longitude', 'description', 'olt_id', 'onu_sn', 'onu_index', 'onu_type'];
    public const PHOTOS = ['identity_photo', 'house_photo', 'cpe_photo'];

    public function __construct(private CustomerLifecycleService $lifecycle)
    {
    }

    /** Aturan validasi bersama (web & API). */
    public static function activationRules(): array
    {
        return [
            'modem_stock_id' => 'nullable|exists:inventory_stocks,id',
            'consumables' => 'nullable|array',
            'consumables.*.item_id' => 'required|exists:inventory_items,id',
            'consumables.*.quantity' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,transfer,pg',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'activated_at' => 'nullable|date',
            'odc_id' => 'nullable|exists:odcs,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|numeric',
            'vlan_id' => 'nullable|numeric',
            'static_ip' => 'nullable|string',
            'region_code' => 'nullable|string',
            'sto_code' => 'nullable|string',
            'stb_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'identity_photo' => 'nullable|image|max:8192',
            'house_photo' => 'nullable|image|max:8192',
            'cpe_photo' => 'nullable|image|max:8192',   // foto kamera ponsel; form web mengecilkannya dulu
            'description' => 'nullable|string',
            'olt_id' => 'nullable|exists:olts,id',
            'onu_sn' => 'nullable|string',
            'onu_index' => 'nullable|string',
            'onu_type' => 'nullable|string',
        ];
    }

    /** Pilihan untuk form aktivasi. */
    public function activationOptions(): array
    {
        return [
            'serial_items' => InventoryItem::where('track_serial', true)
                ->whereHas('stocks', fn ($q) => $q->available())
                ->with(['stocks' => fn ($q) => $q->available()->orderBy('serial_number')])->get(),
            'consumable_items' => InventoryItem::where('track_serial', false)->get(),
            'olts' => \App\Models\Olt::where('is_active', true)->get(),
            'regions' => \App\Models\Region::all(),
            'stos' => \App\Models\Sto::all(),
            'stbs' => \App\Models\Stb::all(),
            'odcs' => \App\Models\Odc::all(),
            'odps' => \App\Models\Odp::all(),
            'bank_accounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get(['id', 'bank_name', 'account_name', 'type']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data  input tervalidasi
     * @param  array<string, UploadedFile|null>  $photos
     * @return array{customer: Customer, installation_invoice: ?Invoice, installation_report: ?InstallationReport}
     *
     * @throws \DomainException bila status/perangkat tidak memenuhi syarat
     */
    public function activate(Customer $customer, array $data, ?User $by, array $photos = []): array
    {
        if (! in_array($customer->status, self::ACTIVATABLE, true)) {
            throw new \DomainException("Pelanggan berstatus '{$customer->status}' tidak bisa diaktivasi lewat wizard (hanya baru / menunggu aktivasi).");
        }

        $modem = null;
        if (! empty($data['modem_stock_id'])) {
            $modem = InventoryStock::with('item')->find($data['modem_stock_id']);
            if (! $modem || ! $modem->isAvailable()) {
                throw new \DomainException('Modem yang dipilih tidak tersedia di gudang (sudah terpasang atau tidak siap pakai).');
            }
        }

        $method = $data['payment_method'] ?? null;
        $invoice = null;
        $installation = app(InstallationService::class);
        $invoiceMode = $installation->invoiceEnabled();   // selalu terbitkan invoice instalasi (opsional)
        $graceHours = $installation->graceHours();

        DB::transaction(function () use ($customer, $data, $by, $photos, $modem, $method, $invoiceMode, $graceHours) {
            $update = [
                'is_active' => true,
                'status' => Customer::STATUS_ACTIVE,
                'activated_at' => ! empty($data['activated_at']) ? \Carbon\Carbon::parse($data['activated_at']) : now(),
                // Mode invoice: lunas ditandai oleh pembayaran invoice (InvoiceService), bukan di sini
                'installation_paid_at' => ($invoiceMode || $method === 'pg') ? null : now(),
                'activation_grace_expires_at' => (! $invoiceMode && $method === 'pg' && $graceHours > 0) ? now()->addHours($graceHours) : null,
                'installation_bank_account_id' => $data['bank_account_id'] ?? null,
            ];
            foreach (self::FIELDS as $f) {
                if (array_key_exists($f, $data)) {
                    $update[$f] = $data[$f];
                }
            }
            if ($modem && empty($update['onu_sn'])) {
                $update['onu_sn'] = $modem->serial_number;
            }
            foreach (self::PHOTOS as $p) {
                if (($photos[$p] ?? null) instanceof UploadedFile) {
                    $update[$p] = $photos[$p]->store('kyc_photos', 'public');
                }
            }
            // Kunci & periksa ulang modem: dua aktivasi bersamaan tidak boleh memakai unit yang sama
            if ($modem && ! InventoryStock::whereKey($modem->id)->lockForUpdate()->first()?->isAvailable()) {
                throw new \DomainException('Modem yang dipilih baru saja dipakai aktivasi lain.');
            }
            $customer->update($update);

            // Biaya instalasi tunai/transfer langsung masuk Kas & Bank (mode lama, tanpa invoice)
            if (! $invoiceMode && $customer->installation_fee > 0 && in_array($method, ['cash', 'transfer'], true)) {
                $accountId = $data['bank_account_id'] ?? null;
                if ($method === 'cash' && ! $accountId) {
                    $accountId = BankAccount::firstOrCreate(['bank_name' => 'KAS TUNAI'],
                        ['account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'is_active' => true])->id;
                }
                if ($accountId) {
                    BankTransaction::create([
                        'bank_account_id' => $accountId, 'type' => 'deposit', 'amount' => $customer->installation_fee,
                        'description' => '[Instalasi] Pelanggan: ' . $customer->name . ' (' . $customer->customer_code . ')',
                        'transaction_date' => now(), 'created_by' => $by?->id,
                    ]);
                }
            }

            $customer->syncBillingDates();

            Ticket::where('customer_id', $customer->id)->where('type', 'aktivasi')->whereIn('status', ['open', 'in_progress'])->get()
                ->each(fn (Ticket $t) => $t->update(['status' => 'resolved', 'resolution_notes' => 'Aktivasi selesai. Perangkat telah dipasang.']));

            if ($modem) {
                $modem->update(['status' => 'installed', 'customer_id' => $customer->id]);
                InventoryMovement::create([
                    'inventory_item_id' => $modem->inventory_item_id, 'customer_id' => $customer->id, 'type' => 'out', 'quantity' => 1,
                    'reference' => 'Activation: ' . $customer->customer_code,
                    'notes' => 'Installed at Customer: ' . $customer->name . ' (SN: ' . $modem->serial_number . ')', 'user_id' => $by?->id,
                ]);
            }
            foreach ($data['consumables'] ?? [] as $c) {
                if ((float) $c['quantity'] > 0) {
                    InventoryMovement::create([
                        'inventory_item_id' => $c['item_id'], 'customer_id' => $customer->id, 'type' => 'out', 'quantity' => $c['quantity'],
                        'reference' => 'Activation: ' . $customer->customer_code, 'notes' => 'Consumed for installation', 'user_id' => $by?->id,
                    ]);
                }
            }
        });

        if ($invoiceMode) {
            // Selalu terbit: lunas di lokasi (tunai/transfer) atau belum dibayar dengan batas waktu
            $invoice = $installation->issueInstallationInvoice($customer->fresh(), $method, $data['bank_account_id'] ?? null);
        } elseif ($method === 'pg' && $customer->installation_fee > 0) {
            // Mode lama: invoice hanya untuk pembayaran lewat gateway
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateUniqueInvoiceNumber('INV-INST'),
                'customer_id' => $customer->id, 'amount' => $customer->installation_fee, 'subtotal' => $customer->installation_fee,
                'status' => 'unpaid', 'due_date' => now()->addDays(3), 'notes' => 'Biaya Instalasi Pelanggan: ' . $customer->name,
            ]);
        }

        $report = $installation->baEnabled() ? $installation->createReport($customer->fresh(), $by, $invoice) : null;
        $installation->notifyCustomer($customer->fresh(), $report, $invoice?->fresh());

        return ['customer' => $customer->fresh(), 'installation_invoice' => $invoice?->fresh(), 'installation_report' => $report];
    }

    /** Perangkat terpasang di pelanggan (untuk form dismantle). */
    public function installedEquipment(Customer $customer)
    {
        return InventoryStock::where('customer_id', $customer->id)->where('status', 'installed')->with('item')->get();
    }

    /**
     * @param  int[]  $stockIds  perangkat yang berhasil ditarik (boleh kosong bila tidak ada perangkat)
     *
     * @throws \DomainException
     */
    public function dismantle(Customer $customer, array $stockIds, ?User $by, ?string $notes = null): Customer
    {
        if (in_array($customer->status, [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED], true)) {
            throw new \DomainException('Pelanggan ini sudah dicabut / dibatalkan.');
        }

        $stocks = InventoryStock::whereIn('id', $stockIds)->get();
        $foreign = $stocks->reject(fn ($s) => (int) $s->customer_id === $customer->id && $s->status === 'installed');
        if ($foreign->isNotEmpty() || $stocks->count() !== count(array_unique($stockIds))) {
            throw new \DomainException('Ada perangkat yang tidak terpasang di pelanggan ini: ' . $foreign->pluck('serial_number')->implode(', '));
        }

        $username = $customer->username;
        DB::transaction(function () use ($customer, $stocks, $by, $notes) {
            $customer->update(['is_active' => false, 'status' => Customer::STATUS_DISMANTLED]);

            Ticket::where('customer_id', $customer->id)->where('type', 'dismantle')->whereIn('status', ['open', 'in_progress'])->get()
                ->each(fn (Ticket $t) => $t->update(['status' => 'resolved',
                    'resolution_notes' => trim('Dismantle selesai. ' . $stocks->count() . ' perangkat ditarik. ' . ($notes ?? ''))]));

            foreach ($stocks as $stock) {
                $sn = $stock->serial_number;
                $stock->update(['status' => 'dismantled', 'customer_id' => null, 'condition' => 'used']);
                InventoryMovement::create([
                    'inventory_item_id' => $stock->inventory_item_id, 'customer_id' => $customer->id, 'type' => 'in', 'quantity' => 1,
                    'reference' => 'Dismantle: ' . ($customer->customer_code ?? 'Manual'), 'notes' => 'Equipment returned (SN: ' . $sn . ')',
                    'user_id' => $by?->id,
                ]);
            }
        });

        if ($customer->olt_id && $customer->onu_index && $customer->onu_sn) {
            \App\Jobs\DeprovisionOnuJob::dispatch($customer->olt_id, $customer->onu_index, $customer->onu_sn);
        }
        if ($username) {
            $this->lifecycle->removeRadiusAccount($username);   // radcheck, radreply, radusergroup
            try {
                $this->lifecycle->disconnectSessions($username);
            } catch (\Throwable $e) {
                Log::warning('CoA disconnect gagal saat dismantle: ' . $e->getMessage());
            }
        }

        return $customer->fresh();
    }
}
