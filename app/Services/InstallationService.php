<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\InstallationReport;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Setelah aktivasi (opsional, Pengaturan Instalasi):
 *  - Berita Acara (BA) instalasi bernomor + tanda tangan digital pelanggan & teknisi.
 *  - Invoice instalasi selalu terbit; dibayar di lokasi -> langsung lunas, belum dibayar ->
 *    batas bayar N jam, lalu diisolir (customer:check-grace-period), pengingat WhatsApp sebelum batas.
 */
class InstallationService
{
    public const DEFAULT_TERMS = "1. Perangkat (modem/ONU, adaptor, kabel) yang dipasang merupakan milik perusahaan dan dipinjamkan selama berlangganan.\n"
        . "2. Pelanggan wajib menjaga perangkat; kerusakan atau kehilangan akibat kelalaian pelanggan dikenakan biaya penggantian.\n"
        . "3. Saat berhenti berlangganan, perangkat wajib dikembalikan dalam keadaan lengkap.\n"
        . "4. Layanan dinyatakan aktif dan berfungsi baik pada saat Berita Acara ini ditandatangani.\n"
        . "5. Biaya instalasi dibayar sesuai invoice; keterlambatan pembayaran dapat mengakibatkan layanan diisolir sementara.";

    public const SETTINGS = [
        'activation_ba_enabled' => '0',
        'installation_invoice_enabled' => '0',
        'installation_grace_hours' => '24',
        'installation_reminder_hours' => '3',
        'installation_ba_terms' => self::DEFAULT_TERMS,
    ];

    public function setting(string $key): string
    {
        return (string) (get_setting($key, self::SETTINGS[$key]) ?? self::SETTINGS[$key]);
    }

    public function baEnabled(): bool
    {
        return $this->setting('activation_ba_enabled') === '1';
    }

    public function invoiceEnabled(): bool
    {
        return $this->setting('installation_invoice_enabled') === '1';
    }

    public function graceHours(): int
    {
        return max(0, min(720, (int) $this->setting('installation_grace_hours')));
    }

    public function reminderHours(): int
    {
        return max(0, min(48, (int) $this->setting('installation_reminder_hours')));
    }

    public function saveSettings(array $values): void
    {
        foreach (array_keys(self::SETTINGS) as $key) {
            if (array_key_exists($key, $values)) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $values[$key]]);
            }
        }
        Cache::forget('app_settings');
    }

    // ------------------------------------------------------------- invoice

    /**
     * Invoice instalasi saat "selalu terbitkan invoice" aktif. Dibayar di lokasi (tunai/transfer)
     * -> langsung lunas lewat InvoiceService (kuitansi, kas, jurnal). Belum dibayar -> batas bayar.
     */
    public function issueInstallationInvoice(Customer $customer, ?string $method, ?int $bankAccountId): ?Invoice
    {
        $fee = (float) $customer->installation_fee;
        if ($fee <= 0) {
            return null;   // instalasi gratis/promo: tanpa invoice & tanpa batas bayar
        }

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateUniqueInvoiceNumber('INV-INST'),
            'customer_id' => $customer->id, 'amount' => $fee, 'subtotal' => $fee,
            'status' => 'unpaid', 'due_date' => now()->addHours(max(1, $this->graceHours()))->toDateString(),
            'notes' => 'Biaya Instalasi Pelanggan: ' . $customer->name,
        ]);

        if (in_array($method, ['cash', 'transfer'], true)) {
            if ($method === 'cash' && ! $bankAccountId) {
                $bankAccountId = BankAccount::firstOrCreate(['bank_name' => 'KAS TUNAI'],
                    ['account_name' => 'Kas Kantor Utama', 'type' => 'cash', 'is_active' => true])->id;
            }
            app(InvoiceService::class)->markAsPaid($invoice, $bankAccountId);
            $invoice->update(['payment_method' => $method]);
        } else {
            $hours = $this->graceHours();
            $customer->update([
                'installation_paid_at' => null,
                'activation_grace_expires_at' => $hours > 0 ? now()->addHours($hours) : null,
                'installation_reminder_sent_at' => null,
            ]);
        }

        return $invoice->fresh();
    }

    // ------------------------------------------------------------ berita acara

    public function createReport(Customer $customer, ?User $technician, ?Invoice $invoice = null): InstallationReport
    {
        $customer->loadMissing('package');
        $devices = InventoryStock::with('item')->where('customer_id', $customer->id)->where('status', 'installed')->get()
            ->map(fn ($s) => ['item' => $s->item?->name, 'serial_number' => $s->serial_number, 'mac_address' => $s->mac_address, 'condition' => $s->condition])->values()->all();
        $materials = InventoryMovement::with('item')->where('customer_id', $customer->id)->where('type', 'out')
            ->where('reference', 'Activation: ' . $customer->customer_code)
            ->get()->filter(fn ($m) => ! $m->item?->track_serial)
            ->map(fn ($m) => ['item' => $m->item?->name, 'quantity' => (float) $m->quantity, 'unit' => $m->item?->unit])->values()->all();

        $snapshot = [
            'customer' => [
                'name' => $customer->name, 'customer_code' => $customer->customer_code, 'username' => $customer->username,
                'phone' => $customer->phone, 'address' => $customer->address, 'ktp' => $customer->ktp,
            ],
            'package' => ['name' => $customer->package?->name, 'price' => (float) ($customer->package?->price ?? 0),
                'speed' => trim(($customer->package?->download_speed ?? '') . '/' . ($customer->package?->upload_speed ?? ''), '/')],
            'installation_fee' => (float) $customer->installation_fee,
            'location' => ['latitude' => $customer->latitude, 'longitude' => $customer->longitude,
                'odp_port' => $customer->odp_port, 'vlan_id' => $customer->vlan_id],
            'network' => ['onu_sn' => $customer->onu_sn, 'onu_index' => $customer->onu_index, 'static_ip' => $customer->static_ip],
            'devices' => $devices,
            'materials' => $materials,
            'photos' => array_filter(['identity' => $customer->identity_photo, 'house' => $customer->house_photo, 'cpe' => $customer->cpe_photo]),
            'technician' => $technician?->name,
            'activated_at' => $customer->activated_at?->toIso8601String(),
            'invoice' => $invoice ? ['number' => $invoice->invoice_number, 'amount' => (float) $invoice->amount, 'status' => $invoice->status] : null,
        ];

        return DB::transaction(fn () => InstallationReport::create([
            'number' => $this->nextNumber(),
            'customer_id' => $customer->id,
            'technician_id' => $technician?->id,
            'snapshot' => $snapshot,
            'terms' => $this->setting('installation_ba_terms'),
            'sign_token' => Str::random(48),
        ]));
    }

    private function nextNumber(): string
    {
        $prefix = 'BA/' . now()->format('Y/m') . '/';
        $last = InstallationReport::where('number', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan tanda tangan (gambar PNG data-URL dari kanvas).
     *
     * @throws \DomainException
     */
    public function sign(InstallationReport $report, string $role, string $dataUrl, ?string $signerName = null, array $meta = []): InstallationReport
    {
        if (! in_array($role, ['customer', 'technician'], true)) {
            throw new \DomainException('Peran penanda tangan tidak dikenal.');
        }
        if ($report->{$role . '_signed_at'}) {
            throw new \DomainException('BA ini sudah ditandatangani oleh ' . ($role === 'customer' ? 'pelanggan' : 'teknisi') . '.');
        }
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            throw new \DomainException('Format tanda tangan tidak valid.');
        }
        $png = base64_decode($m[1], true);
        $info = ($png !== false && strlen($png) <= 512 * 1024) ? @getimagesizefromstring($png) : false;
        if (! $info || ($info['mime'] ?? null) !== 'image/png' || $info[0] > 4000 || $info[1] > 4000) {
            throw new \DomainException('Gambar tanda tangan tidak valid atau terlalu besar.');
        }

        $path = 'ba_signatures/' . Str::slug($report->number) . "-{$role}-" . Str::random(6) . '.png';
        Storage::disk('public')->put($path, $png);

        $fields = [$role . '_signature' => $path, $role . '_signed_at' => now(),
            'sign_meta' => array_merge($report->sign_meta ?? [], [$role => array_filter($meta)])];
        if ($role === 'customer') {
            $fields['customer_signer_name'] = $signerName ?: ($report->snapshot['customer']['name'] ?? null);
        }
        $report->update($fields);

        return $report->fresh();
    }

    // --------------------------------------------------------- notifikasi WA

    /** Pesan WhatsApp ke pelanggan setelah aktivasi: tautan BA (bila belum ditandatangani) & invoice. */
    public function notifyCustomer(Customer $customer, ?InstallationReport $report, ?Invoice $invoice): void
    {
        if (! $customer->phone || (! $report && ! ($invoice && $invoice->status === 'unpaid'))) {
            return;
        }
        $lines = ["Halo *{$customer->name}*,", '', 'Pemasangan internet Anda telah selesai. Terima kasih telah berlangganan.'];
        if ($report) {
            $lines[] = '';
            $lines[] = $report->customer_signed_at
                ? "Berita Acara Instalasi *{$report->number}*: {$report->signUrl()}"
                : "Mohon periksa & tanda tangani Berita Acara Instalasi *{$report->number}*: {$report->signUrl()}";
        }
        if ($invoice && $invoice->status === 'unpaid') {
            $deadline = $customer->activation_grace_expires_at?->locale('id')->translatedFormat('d F Y H:i');
            $lines[] = '';
            $lines[] = 'Tagihan instalasi *Rp ' . number_format((float) $invoice->amount, 0, ',', '.') . '*: ' . route('portal.pay', $invoice->invoice_number);
            if ($deadline) {
                $lines[] = "Mohon dibayar sebelum *{$deadline}* agar layanan tidak diisolir sementara.";
            }
        }
        \App\Jobs\SendCustomWhatsappMessageJob::dispatch($customer->phone, implode("\n", $lines));
    }
}
