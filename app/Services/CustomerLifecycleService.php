<?php

namespace App\Services;

use App\Jobs\DeprovisionOnuJob;
use App\Models\Customer;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat untuk perubahan siklus hidup pelanggan (hapus, aktivasi,
 * isolir, dismantle) beserta efeknya ke RADIUS dan OLT.
 *
 * Dipakai oleh controller Web dan API agar perilaku keduanya identik.
 */
class CustomerLifecycleService
{
    public const BULK_ACTIONS = ['activate', 'suspend', 'dismantle', 'delete'];

    public function __construct(protected RadiusCoAService $coa)
    {
    }

    /**
     * Hapus pelanggan: akun RADIUS dibersihkan (agar tidak bisa login PPPoE lagi),
     * sesi aktif diputus, dan ONU dihapus dari OLT bila terdaftar.
     */
    public function delete(Customer $customer): void
    {
        $username = $customer->username;
        $oltId = $customer->olt_id;
        $onuIndex = $customer->onu_index;
        $onuSn = $customer->onu_sn;

        DB::transaction(function () use ($customer, $username) {
            $this->removeRadiusAccount($username);
            $customer->delete();
        });

        // Efek ke perangkat jaringan dilakukan setelah data tersimpan.
        $this->disconnectSessions($username);

        if ($oltId && $onuIndex) {
            DeprovisionOnuJob::dispatch($oltId, $onuIndex, $onuSn);
        }
    }

    /**
     * Aktifkan pelanggan baru / menunggu aktivasi. Pelanggan terisolir, dicabut,
     * atau batal tidak diaktifkan lewat jalur ini.
     */
    public function activate(Customer $customer): bool
    {
        if (! in_array($customer->status, [Customer::STATUS_NEW, Customer::STATUS_WAITING_ACTIVATION], true)) {
            return false;
        }

        $customer->update([
            'is_active' => true,
            'status' => Customer::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);
        $customer->syncBillingDates();

        // Putus sesi lama agar profil layanan baru langsung berlaku.
        $this->disconnectSessions($customer->username);

        return true;
    }

    /** Isolir pelanggan yang sedang aktif. */
    public function suspend(Customer $customer): bool
    {
        if (! $customer->is_active) {
            return false;
        }

        $customer->update([
            'is_active' => false,
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        $this->disconnectSessions($customer->username);

        return true;
    }

    /** Ajukan dismantle (Observer membuat tiket penarikan perangkat). */
    public function requestDismantle(Customer $customer): bool
    {
        if (in_array($customer->status, [Customer::STATUS_WAITING_DISMANTLE, Customer::STATUS_DISMANTLED], true)) {
            return false;
        }

        $customer->update(['status' => Customer::STATUS_WAITING_DISMANTLE]);

        return true;
    }

    /**
     * Jalankan aksi massal; mengembalikan jumlah pelanggan yang benar-benar berubah.
     *
     * @param  Collection<int, Customer>  $customers
     */
    public function bulk(string $action, Collection $customers): int
    {
        if (! in_array($action, self::BULK_ACTIONS, true)) {
            throw new \InvalidArgumentException("Aksi massal tidak dikenal: {$action}");
        }

        $count = 0;
        foreach ($customers as $customer) {
            $changed = match ($action) {
                'activate' => $this->activate($customer),
                'suspend' => $this->suspend($customer),
                'dismantle' => $this->requestDismantle($customer),
                'delete' => (function () use ($customer) { $this->delete($customer); return true; })(),
            };
            $count += $changed ? 1 : 0;
        }

        return $count;
    }

    /** Hapus semua data RADIUS milik username (login, atribut balasan, grup/profil). */
    public function removeRadiusAccount(string $username): void
    {
        RadCheck::where('username', $username)->delete();
        RadReply::where('username', $username)->delete();
        RadUserGroup::where('username', $username)->delete();
    }

    /**
     * Putus semua sesi aktif pelanggan. Router dicari dari sesi itu sendiri
     * (radacct.nasipaddress = nas.nasname), sehingga benar untuk lebih dari satu NAS.
     *
     * @return int jumlah sesi yang perintah putusnya berhasil dikirim
     */
    public function disconnectSessions(string $username): int
    {
        $sent = 0;

        try {
            $sessions = RadAcct::online()->where('username', $username)->get();
        } catch (\Throwable $e) {
            Log::warning("Tidak bisa membaca sesi aktif {$username}: " . $e->getMessage());
            return 0;
        }

        foreach ($sessions as $session) {
            $nas = Nas::where('nasname', $session->nasipaddress)->first();
            if (! $nas) {
                Log::warning("Putus sesi {$username}: NAS {$session->nasipaddress} tidak terdaftar.");
                continue;
            }
            if ($this->coa->disconnect($nas->nasname, (string) $nas->secret, $username, $session->acctsessionid)) {
                $sent++;
            }
        }

        return $sent;
    }
}
