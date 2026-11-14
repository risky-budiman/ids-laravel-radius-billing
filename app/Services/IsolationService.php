<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerIsolation;
use App\Models\Radius\RadGroupReply;
use App\Models\Radius\RadReply;
use App\Models\Radius\RadUserGroup;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Isolir lewat RADIUS: pelanggan terisolir dipindah ke grup ISOLIR dan mendapat IP isolir.
 *
 *  - mode "range": aplikasi mengalokasikan satu IP dari rentang yang diatur pengguna
 *    (Framed-IP-Address per pelanggan, tercatat di customer_isolations).
 *  - mode "pool" : RADIUS mengirim Framed-Pool; IP dibagikan MikroTik dari pool tersebut.
 *
 * Grup paket & atribut IP asli (Framed-IP-Address / Framed-Pool) disimpan lalu dipulihkan
 * saat isolir dicabut. Perubahan baru berlaku setelah sesi PPPoE tersambung ulang — pemanggil
 * (jalur isolir / lunas) sudah memutus sesi.
 */
class IsolationService
{
    public const SETTINGS = [
        'isolir_enabled' => '0',
        'isolir_mode' => 'range',
        'isolir_group' => 'ISOLIR',
        'isolir_ip_start' => '',
        'isolir_ip_end' => '',
        'isolir_pool_name' => '',
        'isolir_rate_limit' => '',
        'isolir_address_list' => '',
        'isolir_mikrotik_group' => '',
        'isolir_page_host' => '',
    ];

    /** Atribut IP milik user yang harus disingkirkan sementara agar IP isolir berlaku. */
    private const IP_ATTRIBUTES = ['Framed-IP-Address', 'Framed-Pool'];

    public function config(): array
    {
        $cfg = [];
        foreach (self::SETTINGS as $key => $default) {
            $cfg[$key] = (string) (get_setting($key, $default) ?? $default);
        }

        return $cfg;
    }

    public function enabled(): bool
    {
        return $this->config()['isolir_enabled'] === '1';
    }

    public function groupName(): string
    {
        return $this->config()['isolir_group'] ?: 'ISOLIR';
    }

    // ------------------------------------------------------------------ isolir

    /**
     * Pasang isolir RADIUS untuk pelanggan (idempoten). Mengembalikan catatan isolir,
     * atau null bila fitur dimatikan.
     */
    public function isolate(Customer $customer): ?CustomerIsolation
    {
        if (! $this->enabled() || ! $customer->username) {
            return null;
        }

        $cfg = $this->config();

        return DB::transaction(function () use ($customer, $cfg) {
            $record = CustomerIsolation::where('customer_id', $customer->id)->lockForUpdate()->first();
            $group = $this->groupName();

            if (! $record) {
                $groups = RadUserGroup::where('username', $customer->username)
                    ->where('groupname', '!=', $group)
                    ->get(['groupname', 'priority'])->map(fn ($g) => ['groupname' => $g->groupname, 'priority' => (int) $g->priority])->values()->all();
                $replies = RadReply::where('username', $customer->username)
                    ->whereIn('attribute', self::IP_ATTRIBUTES)
                    ->get(['attribute', 'op', 'value'])->map(fn ($r) => $r->only(['attribute', 'op', 'value']))->values()->all();

                $record = CustomerIsolation::create([
                    'customer_id' => $customer->id,
                    'username' => $customer->username,
                    'mode' => $cfg['isolir_mode'],
                    'previous_groups' => $groups,
                    'previous_replies' => $replies,
                    'isolated_at' => now(),
                ]);
            } elseif ($record->username !== $customer->username) {
                // Username diganti saat terisolir: IP isolir ikut pindah ke username baru.
                RadReply::where('username', $record->username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
                $record->username = $customer->username;
                $record->save();
            }

            // Grup: hanya ISOLIR (atribut paket tidak ikut terkirim)
            RadUserGroup::where('username', $customer->username)->delete();
            RadUserGroup::insert(['username' => $customer->username, 'groupname' => $group, 'priority' => 0]);

            // Atribut IP asli disingkirkan; IP isolir dipasang sesuai mode
            RadReply::where('username', $customer->username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
            $this->applyAddress($record, $cfg);

            return $record->fresh();
        });
    }

    /** Cabut isolir: pulihkan grup paket & atribut IP asli, lepaskan IP isolir. */
    public function release(Customer $customer, bool $restoreRadius = true): void
    {
        $record = CustomerIsolation::where('customer_id', $customer->id)->first();
        if (! $record) {
            return;
        }

        DB::transaction(function () use ($customer, $record, $restoreRadius) {
            $username = $customer->username ?: $record->username;

            if ($restoreRadius && $username) {
                RadUserGroup::where('username', $username)->delete();
                // Paket pelanggan saat ini menang (paket bisa diganti selama terisolir);
                // grup tersimpan hanya dipakai bila pelanggan tidak punya paket.
                $groups = $customer->package
                    ? [['groupname' => $customer->package->name, 'priority' => 1]]
                    : ($record->previous_groups ?: []);
                foreach ($groups as $g) {
                    RadUserGroup::insert(['username' => $username, 'groupname' => $g['groupname'], 'priority' => $g['priority'] ?? 1]);
                }

                RadReply::where('username', $username)->whereIn('attribute', self::IP_ATTRIBUTES)->delete();
                foreach ($record->previous_replies ?: [] as $r) {
                    RadReply::create(['username' => $username, 'attribute' => $r['attribute'], 'op' => $r['op'] ?: ':=', 'value' => $r['value']]);
                }
            }

            $record->delete();
        });
    }

    public function isIsolated(Customer $customer): bool
    {
        return CustomerIsolation::where('customer_id', $customer->id)->exists();
    }

    // -------------------------------------------------------------- pengaturan

    /**
     * Simpan pengaturan, tulis atribut grup ISOLIR ke radgroupreply, lalu terapkan ulang ke
     * semua pelanggan terisolir (IP di luar rentang baru dialokasikan ulang).
     *
     * @return array{isolated: int, released: int, without_ip: int}
     */
    public function saveSettings(array $values): array
    {
        $oldGroup = $this->groupName();

        foreach (self::SETTINGS as $key => $default) {
            if (array_key_exists($key, $values)) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) ($values[$key] ?? '')]);
            }
        }
        Cache::forget('app_settings');

        $this->syncGroupReplies($oldGroup);

        return $this->reapplyAll();
    }

    /** Atribut balasan grup ISOLIR (radgroupreply) sesuai pengaturan. */
    public function syncGroupReplies(?string $oldGroup = null): void
    {
        $cfg = $this->config();
        $group = $this->groupName();

        RadGroupReply::whereIn('groupname', array_unique(array_filter([$oldGroup, $group])))->delete();

        $rows = [];
        if ($cfg['isolir_mode'] === 'pool' && $cfg['isolir_pool_name'] !== '') {
            $rows['Framed-Pool'] = $cfg['isolir_pool_name'];
        }
        if ($cfg['isolir_rate_limit'] !== '') {
            $rows['Mikrotik-Rate-Limit'] = $cfg['isolir_rate_limit'];
        }
        if ($cfg['isolir_address_list'] !== '') {
            $rows['Mikrotik-Address-List'] = $cfg['isolir_address_list'];
        }
        if ($cfg['isolir_mikrotik_group'] !== '') {
            $rows['Mikrotik-Group'] = $cfg['isolir_mikrotik_group'];
        }
        foreach ($rows as $attribute => $value) {
            // ":=" agar menimpa atribut sejenis milik user (mis. batas kecepatan FUP)
            RadGroupReply::create(['groupname' => $group, 'attribute' => $attribute, 'op' => ':=', 'value' => $value]);
        }
    }

    /** Samakan kondisi RADIUS semua pelanggan terisolir dengan pengaturan saat ini. */
    public function reapplyAll(): array
    {
        $stats = ['isolated' => 0, 'released' => 0, 'without_ip' => 0];

        if (! $this->enabled()) {
            foreach (CustomerIsolation::with('customer')->get() as $record) {
                if ($record->customer) {
                    $this->release($record->customer);
                } else {
                    $record->delete();
                }
                $stats['released']++;
            }

            return $stats;
        }

        $customers = Customer::where('status', Customer::STATUS_SUSPENDED)->whereNotNull('username')->get();
        foreach ($customers as $customer) {
            $record = $this->isolate($customer);
            $stats['isolated']++;
            if ($record && $record->mode === 'range' && ! $record->ip_address) {
                $stats['without_ip']++;
            }
        }

        // Catatan isolir yatim (pelanggan sudah tidak terisolir) dibersihkan
        CustomerIsolation::whereNotIn('customer_id', $customers->pluck('id'))->get()
            ->each(function ($r) use (&$stats) {
                $r->customer ? $this->release($r->customer) : $r->delete();
                $stats['released']++;
            });

        return $stats;
    }

    // ------------------------------------------------------------ alokasi IP

    private function applyAddress(CustomerIsolation $record, array $cfg): void
    {
        $record->mode = $cfg['isolir_mode'];

        if ($cfg['isolir_mode'] !== 'range') {
            $record->ip_address = null;
            $record->save();

            return;
        }

        [$start, $end] = $this->range($cfg);
        $current = $record->ip_address ? ip2long($record->ip_address) : false;
        if ($current === false || $start === null || $current < $start || $current > $end) {
            $record->ip_address = $start === null ? null : $this->nextFreeIp($start, $end, $record->id);
        }
        $record->save();

        if ($record->ip_address) {
            RadReply::create(['username' => $record->username, 'attribute' => 'Framed-IP-Address', 'op' => ':=', 'value' => $record->ip_address]);
        } else {
            Log::warning("Isolir {$record->username}: rentang IP isolir habis atau belum diatur; pelanggan tetap di grup ISOLIR tanpa IP khusus.");
        }
    }

    /** @return array{0: int|null, 1: int|null} */
    public function range(?array $cfg = null): array
    {
        $cfg ??= $this->config();
        $start = ip2long($cfg['isolir_ip_start']);
        $end = ip2long($cfg['isolir_ip_end']);

        if ($start === false || $end === false || $end < $start) {
            return [null, null];
        }

        return [$start, $end];
    }

    public function capacity(): array
    {
        [$start, $end] = $this->range();
        $total = $start === null ? 0 : $end - $start + 1;
        $used = CustomerIsolation::whereNotNull('ip_address')->count();

        return ['total' => $total, 'used' => $used, 'free' => max(0, $total - $used)];
    }

    private function nextFreeIp(int $start, int $end, int $exceptRecordId): ?string
    {
        $used = CustomerIsolation::whereNotNull('ip_address')->whereKeyNot($exceptRecordId)->pluck('ip_address')
            ->map(fn ($ip) => ip2long($ip))->filter(fn ($n) => $n !== false)->flip();

        for ($n = $start; $n <= $end; $n++) {
            $octet = $n & 0xFF;
            if ($octet === 0 || $octet === 255) {
                continue;   // hindari alamat jaringan/broadcast pada batas /24
            }
            if (! $used->has($n)) {
                return long2ip($n);
            }
        }

        return null;
    }

    /** Pelanggan terisolir pemilik IP ini (untuk halaman isolir). */
    public function customerForIp(?string $ip): ?Customer
    {
        if (! $ip) {
            return null;
        }

        return CustomerIsolation::where('ip_address', $ip)->first()?->customer;
    }
}
