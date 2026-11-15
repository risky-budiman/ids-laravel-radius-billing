<?php

namespace App\Console\Commands;

use App\Jobs\SendCustomWhatsappMessageJob;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerLifecycleService;
use App\Services\InstallationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Batas bayar biaya instalasi (Pengaturan Instalasi, default 24 jam):
 *  - pengingat WhatsApp N jam sebelum batas (sekali),
 *  - lewat batas & belum lunas -> diisolir (isolir RADIUS + putus sesi). Begitu invoice instalasi
 *    dibayar, InvoiceService mengaktifkan kembali pelanggan.
 */
class CheckActivationGracePeriod extends Command
{
    protected $signature = 'customer:check-grace-period';

    protected $description = 'Pengingat & isolir pelanggan yang belum membayar biaya instalasi sampai batas waktu';

    public function handle(CustomerLifecycleService $lifecycle, InstallationService $installation)
    {
        $pending = fn () => Customer::where('status', Customer::STATUS_ACTIVE)
            ->whereNotNull('activation_grace_expires_at')
            ->whereNull('installation_paid_at');

        // 1. Pengingat sebelum batas
        $reminderHours = $installation->reminderHours();
        if ($reminderHours > 0) {
            $due = $pending()->whereNull('installation_reminder_sent_at')
                ->where('activation_grace_expires_at', '>', now())
                ->where('activation_grace_expires_at', '<=', now()->addHours($reminderHours))
                ->whereNotNull('phone')->where('phone', '!=', '')
                ->get();
            foreach ($due as $customer) {
                // Tandai dulu agar run berikutnya (tiap menit) tidak mengirim ulang
                if (! DB::table('customers')->where('id', $customer->id)->whereNull('installation_reminder_sent_at')
                    ->update(['installation_reminder_sent_at' => now()])) {
                    continue;
                }
                $invoice = Invoice::where('customer_id', $customer->id)->where('status', 'unpaid')
                    ->where('invoice_number', 'like', 'INV-INST%')->latest('id')->first();
                $deadline = $customer->activation_grace_expires_at->locale('id')->translatedFormat('d F Y H:i');
                $message = "Halo *{$customer->name}*,\n\nPengingat: biaya instalasi internet Anda belum kami terima. "
                    . "Mohon lakukan pembayaran sebelum *{$deadline}* agar layanan tidak diisolir sementara."
                    . ($invoice ? "\n\nBayar di sini: " . route('portal.pay', $invoice->invoice_number) : '')
                    . "\n\nAbaikan pesan ini bila sudah membayar. Terima kasih.";
                SendCustomWhatsappMessageJob::dispatch($customer->phone, $message);
                $this->line("Pengingat instalasi: {$customer->username}");
            }
        }

        // 2. Lewat batas -> isolir
        $expired = $pending()->where('activation_grace_expires_at', '<=', now())->get();
        foreach ($expired as $customer) {
            $this->warn("Isolir {$customer->username}: biaya instalasi belum dibayar sampai batas waktu.");
            try {
                $lifecycle->suspend($customer);
            } catch (\Throwable $e) {
                Log::error("Isolir masa tenggang instalasi gagal untuk {$customer->username}: " . $e->getMessage());
            }
        }

        $this->info('Pemeriksaan masa tenggang instalasi selesai.');
    }
}
