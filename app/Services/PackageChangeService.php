<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageChangeRequest;
use App\Models\Radius\RadUserGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ganti paket lewat pengajuan: CS mengajukan, admin/administrator menyetujui atau menolak.
 * Saat disetujui paket langsung berganti: grup RADIUS paket baru (kecuali sedang terisolir — isolir
 * memulihkan paket terbaru saat dibuka), sesi diputus agar kecepatan baru berlaku, kecepatan ONU
 * diperbarui. Tagihan berikutnya memakai harga paket baru.
 */
class PackageChangeService
{
    public function request(Customer $customer, int $toPackageId, string $reason, ?User $by): PackageChangeRequest
    {
        $to = Package::find($toPackageId);
        if (! $to) {
            throw new \DomainException('Paket tujuan tidak ditemukan.');
        }
        if ((int) $customer->package_id === $to->id) {
            throw new \DomainException('Pelanggan sudah memakai paket ' . $to->name . '.');
        }
        if (in_array($customer->status, Customer::TERMINATED_STATUSES, true)) {
            throw new \DomainException('Pelanggan sudah berhenti / menunggu dismantle; paket tidak bisa diganti.');
        }
        if (PackageChangeRequest::where('customer_id', $customer->id)->where('status', 'pending')->exists()) {
            throw new \DomainException('Masih ada pengajuan ganti paket yang menunggu persetujuan untuk pelanggan ini.');
        }

        return PackageChangeRequest::create([
            'customer_id' => $customer->id, 'from_package_id' => $customer->package_id, 'to_package_id' => $to->id,
            'reason' => mb_substr(trim($reason), 0, 500), 'status' => 'pending', 'requested_by' => $by?->id,
        ]);
    }

    public function approve(PackageChangeRequest $request, ?User $by, ?string $note = null): PackageChangeRequest
    {
        return DB::transaction(function () use ($request, $by, $note) {
            $request = PackageChangeRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw new \DomainException('Pengajuan ini sudah diproses.');
            }
            if ($request->requested_by && $by && $request->requested_by === $by->id && ! $by->isAdministrator()) {
                throw new \DomainException('Pengajuan tidak bisa disetujui oleh pengaju yang sama.');
            }
            $customer = Customer::whereKey($request->customer_id)->lockForUpdate()->firstOrFail();
            if ((int) $customer->package_id !== (int) $request->from_package_id) {
                throw new \DomainException('Paket pelanggan sudah berubah sejak pengajuan dibuat; tolak pengajuan ini dan ajukan ulang.');
            }

            $this->apply($customer, $request->toPackage);
            $request->update(['status' => 'approved', 'reviewed_by' => $by?->id, 'reviewed_at' => now(), 'review_note' => $note]);

            return $request->fresh();
        });
    }

    public function reject(PackageChangeRequest $request, ?User $by, string $note): PackageChangeRequest
    {
        if ($request->status !== 'pending') {
            throw new \DomainException('Pengajuan ini sudah diproses.');
        }
        if (trim($note) === '') {
            throw new \DomainException('Alasan penolakan wajib diisi.');
        }
        $request->update(['status' => 'rejected', 'reviewed_by' => $by?->id, 'reviewed_at' => now(), 'review_note' => mb_substr(trim($note), 0, 500)]);

        return $request->fresh();
    }

    /** Terapkan paket baru ke pelanggan (RADIUS, sesi, ONU). */
    public function apply(Customer $customer, Package $package): void
    {
        $customer->update(['package_id' => $package->id]);
        $isolated = app(IsolationService::class)->isIsolated($customer);

        if ($customer->username && ! $isolated) {
            RadUserGroup::where('username', $customer->username)->delete();
            RadUserGroup::insert(['username' => $customer->username, 'groupname' => $package->name, 'priority' => 1]);
        }
        if ($customer->username) {
            try {
                app(CustomerLifecycleService::class)->disconnectSessions($customer->username);   // kecepatan baru saat login ulang
            } catch (\Throwable $e) {
                Log::warning("Putus sesi setelah ganti paket {$customer->username} gagal: " . $e->getMessage());
            }
        }
        if ($customer->olt_id && $customer->onu_index) {
            \App\Jobs\UpdateOnuJob::dispatch($customer->olt_id, $customer->onu_index, 'update_speed', $package->speed_limit_down ?? 102400);
        }
    }
}
