<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CustomerLifecycleService;
use App\Services\IsolationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pulihkan pelanggan yang rusak oleh bug form edit pelanggan (sebelum v1.5.117):
 *  - setiap simpan memaksa status menjadi aktif/terisolir dari pilihan "Aktif/Terisolir", sehingga pelanggan
 *    baru / menunggu aktivasi menjadi terisolir dan pelanggan dicabut / batal kembali tertagih;
 *  - setiap simpan menghapus koordinat (form tidak punya isian koordinat).
 *
 * Sumber pemulihan = Log Aktivitas (nilai lama & baru setiap perubahan pelanggan). Default hanya laporan;
 * --apply untuk menerapkan. Pelanggan yang datanya sudah diubah lagi setelah kejadian dilewati.
 */
class RecoverCustomerEditBug extends Command
{
    protected $signature = 'customers:recover-edit-bug
        {--apply : Terapkan pemulihan (tanpa ini hanya laporan / dry-run)}
        {--customer= : Hanya pelanggan dengan kode ini}
        {--before= : Hanya kejadian sebelum waktu ini (mis. waktu deploy v1.5.117), format "2026-11-15 14:40"}
        {--skip-status : Jangan pulihkan status}
        {--skip-coordinates : Jangan pulihkan koordinat}';

    protected $description = 'Pulihkan status & koordinat pelanggan yang rusak oleh bug form edit (sebelum v1.5.117) dari Log Aktivitas';

    /** Status yang tidak boleh berubah lewat pilihan Aktif/Terisolir di form edit. */
    private const BUG_FROM = [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION, Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED];

    /** Kolom yang ikut diubah bug status (dikembalikan bila ada di log). */
    private const STATUS_FIELDS = ['status', 'is_active', 'suspended_at', 'terminated_at'];

    public function handle(IsolationService $isolation, CustomerLifecycleService $lifecycle): int
    {
        $apply = (bool) $this->option('apply');
        $before = $this->option('before') ? Carbon::parse($this->option('before')) : null;

        $customerIds = null;
        if ($code = $this->option('customer')) {
            $customerIds = Customer::where('customer_code', $code)->pluck('id');
            if ($customerIds->isEmpty()) {
                $this->error("Pelanggan {$code} tidak ditemukan.");

                return self::FAILURE;
            }
        }

        $logs = ActivityLog::where('subject_type', Customer::class)->where('action', 'updated')
            ->when($customerIds, fn ($q) => $q->whereIn('subject_id', $customerIds))
            ->orderBy('id')->get(['id', 'subject_id', 'user_id', 'properties', 'created_at'])
            ->groupBy('subject_id');

        $this->info(($apply ? 'MENERAPKAN' : 'LAPORAN (dry-run, tanpa perubahan)') . ' — ' . $logs->count() . ' pelanggan punya riwayat perubahan.');
        $rows = [];
        $stats = ['status' => 0, 'coordinates' => 0, 'skipped' => 0, 'invoices' => 0];

        foreach ($logs as $customerId => $entries) {
            $customer = Customer::find($customerId);
            if (! $customer) {
                continue;
            }
            $restore = [];
            $notes = [];
            $statusBug = null;

            if (! $this->option('skip-status')) {
                [$statusBug, $skip] = $this->findStatusBug($customer, $entries, $before);
                if ($statusBug) {
                    $old = (array) $statusBug->properties['old'];
                    foreach (self::STATUS_FIELDS as $f) {
                        if (array_key_exists($f, $old)) {
                            $restore[$f] = $old[$f];
                        }
                    }
                    $notes[] = "status {$statusBug->properties['new']['status']} → {$old['status']} (log #{$statusBug->id}, " . $statusBug->created_at->format('d/m/Y H:i') . ')';
                    $stats['status']++;
                } elseif ($skip) {
                    $rows[] = [$customer->customer_code, $customer->name, 'LEWATI status', $skip];
                    $stats['skipped']++;
                }
            }

            if (! $this->option('skip-coordinates')) {
                [$coordBug, $skip] = $this->findCoordinateBug($customer, $entries, $before);
                if ($coordBug) {
                    $old = (array) $coordBug->properties['old'];
                    $restore['latitude'] = $old['latitude'] ?? null;
                    $restore['longitude'] = $old['longitude'] ?? null;
                    $notes[] = "koordinat {$restore['latitude']}, {$restore['longitude']} (log #{$coordBug->id})";
                    $stats['coordinates']++;
                } elseif ($skip) {
                    $rows[] = [$customer->customer_code, $customer->name, 'LEWATI koordinat', $skip];
                    $stats['skipped']++;
                }
            }

            if (! $restore) {
                continue;
            }

            // Invoice yang terbit selama status salah — hanya dilaporkan (keuangan diputuskan admin)
            $target = $restore['status'] ?? null;
            if ($statusBug && $target && ! in_array($target, Customer::BILLABLE_STATUSES, true)) {
                $invoices = Invoice::where('customer_id', $customer->id)->where('status', 'unpaid')
                    ->where('created_at', '>=', $statusBug->created_at)->pluck('invoice_number');
                if ($invoices->isNotEmpty()) {
                    $notes[] = 'PERIKSA invoice belum lunas: ' . $invoices->implode(', ');
                    $stats['invoices'] += $invoices->count();
                }
            }

            $rows[] = [$customer->customer_code, $customer->name, $apply ? 'DIPULIHKAN' : 'akan dipulihkan', implode('; ', $notes)];
            if ($apply) {
                $this->applyRestore($customer, $restore, $notes, $isolation, $lifecycle);
            }
        }

        if ($rows) {
            $this->table(['Kode', 'Nama', 'Tindakan', 'Rincian'], $rows);
        }
        $this->info("Status: {$stats['status']} · Koordinat: {$stats['coordinates']} · Dilewati: {$stats['skipped']} · Invoice perlu diperiksa: {$stats['invoices']}");
        if (! $apply && ($stats['status'] || $stats['coordinates'])) {
            $this->warn('Belum ada yang diubah. Periksa daftar di atas, lalu jalankan ulang dengan --apply.');
        }

        return self::SUCCESS;
    }

    /**
     * Kejadian bug status pertama: dari baru/menunggu aktivasi/dicabut/batal menjadi aktif/terisolir tanpa
     * aktivasi (aktivasi resmi selalu mengisi activated_at).
     *
     * @return array{0: ?ActivityLog, 1: ?string} [kejadian yang bisa dipulihkan, alasan dilewati]
     */
    private function findStatusBug(Customer $customer, $entries, ?Carbon $before): array
    {
        foreach ($entries as $i => $e) {
            $old = (array) ($e->properties['old'] ?? []);
            $new = (array) ($e->properties['new'] ?? []);
            if (! isset($old['status'], $new['status']) || ($before && $e->created_at->gte($before))) {
                continue;
            }
            if (! in_array($old['status'], self::BUG_FROM, true) || ! in_array($new['status'], Customer::BILLABLE_STATUSES, true)
                || array_key_exists('activated_at', $new)) {
                continue;
            }
            $later = $entries->slice($i + 1)->first(fn ($l) => array_key_exists('status', (array) ($l->properties['new'] ?? [])));
            if ($later) {
                return [null, "status diubah lagi setelah kejadian (log #{$e->id} lalu #{$later->id}); periksa manual"];
            }
            if ($customer->status !== $new['status']) {
                return [null, "status sekarang {$customer->status}, bukan {$new['status']}; periksa manual"];
            }

            return [$e, null];
        }

        return [null, null];
    }

    /** @return array{0: ?ActivityLog, 1: ?string} */
    private function findCoordinateBug(Customer $customer, $entries, ?Carbon $before): array
    {
        foreach ($entries as $i => $e) {
            $old = (array) ($e->properties['old'] ?? []);
            $new = (array) ($e->properties['new'] ?? []);
            if ($before && $e->created_at->gte($before)) {
                continue;
            }
            $wiped = array_key_exists('latitude', $new) && $new['latitude'] === null && filled($old['latitude'] ?? null);
            if (! $wiped) {
                continue;
            }
            $later = $entries->slice($i + 1)->first(fn ($l) => array_key_exists('latitude', (array) ($l->properties['new'] ?? []))
                || array_key_exists('longitude', (array) ($l->properties['new'] ?? [])));
            if ($later) {
                return [null, "koordinat diisi/diubah lagi setelah kejadian (log #{$later->id})"];
            }
            if (filled($customer->latitude) || filled($customer->longitude)) {
                return [null, 'koordinat sudah terisi lagi'];
            }

            return [$e, null];
        }

        return [null, null];
    }

    private function applyRestore(Customer $customer, array $restore, array $notes, IsolationService $isolation, CustomerLifecycleService $lifecycle): void
    {
        $before = $customer->only(array_keys($restore));
        DB::transaction(function () use ($customer, $restore) {
            // Tanpa observer: observer akan menimpa suspended_at/terminated_at dengan waktu sekarang
            $customer->forceFill($restore)->saveQuietly();
        });

        // RADIUS mengikuti status yang dipulihkan
        $target = $restore['status'] ?? null;
        try {
            if (in_array($target, [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION], true) && $isolation->isIsolated($customer)) {
                $isolation->release($customer);            // grup paket dikembalikan; aktivasi tetap lewat wizard
            } elseif (in_array($target, [Customer::STATUS_DISMANTLED, Customer::STATUS_CANCELED], true)) {
                if ($isolation->isIsolated($customer)) {
                    $isolation->release($customer, restoreRadius: false);
                }
                // Form edit lama membuat ulang login RADIUS pelanggan yang sudah dicabut
                $lifecycle->removeRadiusAccount((string) $customer->username);
                $lifecycle->disconnectSessions((string) $customer->username);
            }
        } catch (\Throwable $e) {
            $this->warn("RADIUS {$customer->customer_code}: {$e->getMessage()} — periksa manual.");
        }

        ActivityLog::log('customer_recovered', "Pemulihan bug form edit pelanggan {$customer->customer_code}: " . implode('; ', $notes),
            $customer, ['old' => $before, 'new' => $restore]);
    }
}
