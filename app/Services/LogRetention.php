<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Masa simpan log (Log Aktivitas › Masa simpan log). Semua bawaan "selamanya" (tidak ada yang dihapus).
 *
 * - Log keuangan (invoice, pembayaran, jurnal, kas & bank, COA, pajak, gateway, komisi, pengaturan): minimal 5 tahun.
 * - Log keamanan (login/logout, akun & role staf, hak akses, backup, ekspor data): minimal 1 tahun.
 * - Log operasional lainnya, log WhatsApp, log login PPPoE (radpostauth), notifikasi yang sudah dibaca: sesuai setelan.
 *
 * Penghapusan berjalan harian (logs:prune) per potongan agar tabel tidak terkunci lama.
 */
class LogRetention
{
    public const OPTIONS = [
        'log_retention_general_days' => [0, 90, 180, 365, 730],
        'log_retention_finance_years' => [0, 5, 7, 10],
        'log_retention_whatsapp_days' => [0, 30, 90, 180, 365],
        'log_retention_radius_days' => [0, 30, 90, 180, 365],
        'log_retention_notifications_days' => [0, 30, 90, 180],
    ];

    public const SECURITY_MIN_DAYS = 365;
    public const FINANCE_MIN_YEARS = 5;
    public const CHUNK = 2000;

    /** Subjek log yang menyangkut uang / pembukuan. */
    public const FINANCE_SUBJECTS = ['Invoice', 'Payment', 'Journal', 'JournalEntry', 'ChartOfAccount', 'Tax', 'Gateway', 'PartnerWithdrawal',
        'SalesWithdrawal', 'Setting', 'BankAccount', 'BankTransaction', 'BankStatementLine', 'BankStatementImport', 'BankMutation',
        'PurchaseOrder', 'SalesCommission', 'PartnerCommission', 'JournalItem', 'FixedAsset', 'Expense'];

    public const FINANCE_ACTIONS = ['invoice%', 'payment%', 'bank_%', 'journal%', 'closing%', '%write_off%', '%writeoff%', 'commission%',
        'withdraw%', 'cash%', 'kas_%', 'tax%', 'gateway%', 'deposit%', 'expense%', 'income%', 'transfer%', 'refund%', 'coa%',
        'reconcil%', 'purchase%', 'po_%', 'depreciation%', 'asset%', 'cashflow%'];

    public const SECURITY_ACTIONS = ['login%', 'logout%', 'user_%', 'role_%', 'access%', 'backup%', 'system_%', 'customers_exported',
        'customers_imported', 'isolir_auto_%', 'password%', 'two_factor%', 'logs_pruned', 'logs_retention_updated'];

    public static function get(string $key): int
    {
        $v = (int) Setting::where('key', $key)->value('value');

        return in_array($v, self::OPTIONS[$key] ?? [], true) ? $v : 0;
    }

    public static function set(array $values): void
    {
        foreach (self::OPTIONS as $key => $allowed) {
            if (array_key_exists($key, $values) && in_array((int) $values[$key], $allowed, true)) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) (int) $values[$key], 'type' => 'integer']);
            }
        }
        Cache::forget('app_settings');
    }

    private function financeScope(Builder $q): Builder
    {
        return $q->where(function ($w) {
            // IS NOT NULL eksplisit: "NULL IN (...)" bernilai NULL, sehingga NOT(...) tidak pernah benar untuk log tanpa subjek
            $w->where(fn ($x) => $x->whereNotNull('subject_type')->whereIn('subject_type', array_map(fn ($m) => 'App\\Models\\' . $m, self::FINANCE_SUBJECTS)));
            foreach (self::FINANCE_ACTIONS as $p) {
                $w->orWhere('action', 'like', $p);
            }
        });
    }

    private function securityScope(Builder $q): Builder
    {
        return $q->where(function ($w) {
            foreach (self::SECURITY_ACTIONS as $p) {
                $w->orWhere('action', 'like', $p);
            }
        });
    }

    /**
     * Rencana penghapusan dengan setelan sekarang (atau $override untuk pratinjau).
     *
     * @return list<array{key: string, label: string, keep: string, cutoff: ?\Illuminate\Support\Carbon, count: int}>
     */
    public function plan(array $override = []): array
    {
        $v = fn ($k) => array_key_exists($k, $override) ? (int) $override[$k] : self::get($k);
        $general = $v('log_retention_general_days');
        $financeYears = $v('log_retention_finance_years');
        $rows = [];

        // Log aktivitas: tiga kelompok yang tidak tumpang tindih (keuangan > keamanan > lainnya)
        $financeCut = $financeYears ? now()->subYears(max(self::FINANCE_MIN_YEARS, $financeYears)) : null;
        $securityCut = $general ? now()->subDays(max(self::SECURITY_MIN_DAYS, $general)) : null;
        $generalCut = $general ? now()->subDays($general) : null;
        $rows[] = ['key' => 'finance', 'label' => 'Log aktivitas — keuangan', 'keep' => $financeYears ? "{$financeYears} tahun" : 'selamanya', 'cutoff' => $financeCut,
            'count' => $financeCut ? $this->financeScope(ActivityLog::query())->where('created_at', '<', $financeCut)->count() : 0];
        $rows[] = ['key' => 'security', 'label' => 'Log aktivitas — keamanan', 'keep' => $general ? max(self::SECURITY_MIN_DAYS, $general) . ' hari' : 'selamanya', 'cutoff' => $securityCut,
            'count' => $securityCut ? $this->securityQuery()->where('created_at', '<', $securityCut)->count() : 0];
        $rows[] = ['key' => 'general', 'label' => 'Log aktivitas — operasional lainnya', 'keep' => $general ? "{$general} hari" : 'selamanya', 'cutoff' => $generalCut,
            'count' => $generalCut ? $this->generalQuery()->where('created_at', '<', $generalCut)->count() : 0];

        foreach ([['whatsapp', 'Log WhatsApp', 'whatsapp_logs', 'log_retention_whatsapp_days', null],
                  ['radius', 'Log login PPPoE (RADIUS)', 'radpostauth', 'log_retention_radius_days', 'authdate'],
                  ['notifications', 'Notifikasi yang sudah dibaca', 'notifications', 'log_retention_notifications_days', null]] as [$key, $label, $table, $setting, $col]) {
            $days = $v($setting);
            $cut = $days ? now()->subDays($days) : null;
            $rows[] = ['key' => $key, 'label' => $label, 'keep' => $days ? "{$days} hari" : 'selamanya', 'cutoff' => $cut,
                'count' => $cut && Schema::hasTable($table) ? $this->tableQuery($table, $col, $cut)->count() : 0];
        }

        return $rows;
    }

    private function securityQuery(): Builder
    {
        return $this->securityScope(ActivityLog::query())->whereNot(fn ($q) => $this->financeScope($q));
    }

    private function generalQuery(): Builder
    {
        return ActivityLog::query()->whereNot(fn ($q) => $this->financeScope($q))->whereNot(fn ($q) => $this->securityScope($q));
    }

    private function tableQuery(string $table, ?string $col, $cut)
    {
        $q = DB::table($table)->where($col ?? 'created_at', '<', $cut);

        return $table === 'notifications' ? $q->whereNotNull('read_at') : $q;
    }

    /** Hapus sesuai rencana. @return array<string, int> jumlah terhapus per kelompok */
    public function prune(): array
    {
        $done = [];
        foreach ($this->plan() as $p) {
            if (! $p['cutoff'] || ! $p['count']) {
                continue;
            }
            $builder = match ($p['key']) {
                'finance' => fn () => $this->financeScope(ActivityLog::query())->where('created_at', '<', $p['cutoff']),
                'security' => fn () => $this->securityQuery()->where('created_at', '<', $p['cutoff']),
                'general' => fn () => $this->generalQuery()->where('created_at', '<', $p['cutoff']),
                'whatsapp' => fn () => $this->tableQuery('whatsapp_logs', null, $p['cutoff']),
                'radius' => fn () => $this->tableQuery('radpostauth', 'authdate', $p['cutoff']),
                'notifications' => fn () => $this->tableQuery('notifications', null, $p['cutoff']),
            };
            $n = 0;
            // Per potongan (id) agar tidak mengunci tabel lama
            while ($ids = $builder()->limit(self::CHUNK)->pluck('id')->all()) {
                $table = in_array($p['key'], ['finance', 'security', 'general'], true) ? 'activity_logs'
                    : ['whatsapp' => 'whatsapp_logs', 'radius' => 'radpostauth', 'notifications' => 'notifications'][$p['key']];
                $n += DB::table($table)->whereIn('id', $ids)->delete();
            }
            $done[$p['key']] = $n;
        }

        return $done;
    }
}
