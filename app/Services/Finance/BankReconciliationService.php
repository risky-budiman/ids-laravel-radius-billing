<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Services\Reports\BankStatementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Import mutasi rekening dari internet banking (CSV / Excel, bank apa pun lewat pemetaan kolom) dan
 * rekonsiliasi dengan transaksi Kas & Bank aplikasi: cocok otomatis 1:1 (arah & nominal sama, tanggal ±N hari),
 * daftar "hanya di bank" / "hanya di aplikasi", dan perbandingan saldo bank vs aplikasi.
 */
class BankReconciliationService
{
    public const MATCH_DAYS = 3;
    public const MAX_ROWS = 20000;

    public function __construct(private BankStatementService $statements) {}

    // ---------------------------------------------------------------- membaca file

    /** @return list<list<string>> semua baris sebagai teks (nilai mentah, tanpa format tampilan) */
    public function readRows(string $path, ?string $originalName = null): array
    {
        $ext = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        if (in_array($ext, ['csv', 'txt'], true)) {
            $raw = (string) file_get_contents($path);
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
            if (! mb_check_encoding($raw, 'UTF-8')) {
                $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
            }
            $lines = preg_split('/\r\n|\r|\n/', $raw);
            $delim = $this->guessDelimiter(array_slice($lines, 0, 30));
            $rows = [];
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $rows[] = array_map(fn ($v) => trim((string) $v), str_getcsv($line, $delim, '"', '\\'));
            }
        } else {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = [];
            foreach ($sheet->toArray(null, true, false, false) as $r) {
                if (count(array_filter($r, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $rows[] = array_map(fn ($v) => is_float($v) || is_int($v) ? (string) $v : trim((string) $v), $r);
            }
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('File terlalu besar (lebih dari ' . self::MAX_ROWS . ' baris). Pecah per bulan.');
        }

        return $rows;
    }

    private function guessDelimiter(array $lines): string
    {
        $best = ',';
        $bestScore = -1;
        foreach ([',', ';', "\t", '|'] as $d) {
            $counts = array_values(array_filter(array_map(fn ($l) => substr_count((string) $l, $d), $lines)));
            if (! $counts) {
                continue;
            }
            // pemisah yang paling sering & paling konsisten antarbaris
            $score = array_sum($counts) * (count(array_unique($counts)) === 1 ? 2 : 1);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$d, $score];
            }
        }

        return $best;
    }

    /**
     * Tebak baris judul & kolom dari kata kunci (Tanggal/Date, Keterangan/Description, Debit, Kredit/Credit,
     * Jumlah/Mutasi/Amount, Saldo/Balance, CR/DB).
     */
    public function guessMapping(array $rows): array
    {
        $keys = [
            'date' => '/^(tanggal|tgl|date|trans(action)?\s*date|posting\s*date|tanggal\s*transaksi)\b/i',
            'desc' => '/(keterangan|deskripsi|description|uraian|remark|narasi|berita)/i',
            'debit' => '/^(debit|debet|db|mutasi\s*debit|withdrawal|keluar)\b/i',
            'credit' => '/^(kredit|credit|cr|mutasi\s*kredit|deposit|masuk)\b/i',
            'amount' => '/^(jumlah|mutasi|amount|nominal|nilai)\b/i',
            'balance' => '/^(saldo|balance|saldo\s*akhir)\b/i',
            'type' => '/^(d\/?k|db\/?cr|cr\/?db|tipe|jenis|type)$/i',
        ];
        $best = ['header_row' => 0, 'score' => 0, 'cols' => []];
        foreach (array_slice($rows, 0, 25, true) as $i => $row) {
            $cols = [];
            foreach ($row as $c => $cell) {
                foreach ($keys as $k => $re) {
                    if (! isset($cols[$k]) && preg_match($re, trim((string) $cell))) {
                        $cols[$k] = $c;
                        break;
                    }
                }
            }
            $score = count($cols) + (isset($cols['date']) ? 2 : 0);
            if ($score > $best['score']) {
                $best = ['header_row' => $i, 'score' => $score, 'cols' => $cols];
            }
        }
        $c = $best['cols'];

        return [
            'header_row' => $best['header_row'],
            'date_col' => $c['date'] ?? 0,
            'desc_col' => $c['desc'] ?? 1,
            'amount_mode' => isset($c['debit']) && isset($c['credit']) ? 'split' : 'single',
            'amount_col' => $c['amount'] ?? null,
            'debit_col' => $c['debit'] ?? null,
            'credit_col' => $c['credit'] ?? null,
            'type_col' => $c['type'] ?? null,
            'balance_col' => $c['balance'] ?? null,
            'date_format' => 'auto',
        ];
    }

    // ---------------------------------------------------------------- mengurai nilai

    /** Nominal: "1.234.567,00", "1,234,567.00", "1234567", "(15.000)", "-15.000", "1,000,000.00 CR". */
    public static function parseAmount(mixed $v): ?array
    {
        if (is_int($v) || is_float($v)) {
            return ['value' => (float) $v, 'marker' => null];
        }
        $s = strtoupper(trim((string) $v));
        if ($s === '' || $s === '-') {
            return null;
        }
        $marker = null;
        if (preg_match('/\b(CR|DB|DR|D|K|C)\s*$/', $s, $m)) {
            $marker = in_array($m[1], ['CR', 'K', 'C'], true) ? 'CR' : 'DB';
            $s = trim(substr($s, 0, -strlen($m[0])));
        }
        $neg = false;
        if (preg_match('/^\((.*)\)$/', $s, $m)) {
            [$neg, $s] = [true, $m[1]];
        }
        $s = preg_replace('/[^0-9,.\-]/', '', $s);
        if (str_starts_with($s, '-')) {
            [$neg, $s] = [true, ltrim($s, '-')];
        }
        if ($s === '' || ! preg_match('/\d/', $s)) {
            return null;
        }
        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        if ($lastDot !== false && $lastComma !== false) {
            // pemisah terakhir = desimal
            $s = $lastComma > $lastDot ? str_replace(['.', ','], ['', '.'], $s) : str_replace(',', '', $s);
        } elseif ($lastComma !== false) {
            $s = preg_match('/,\d{1,2}$/', $s) && substr_count($s, ',') === 1 ? str_replace(',', '.', $s) : str_replace(',', '', $s);
        } elseif ($lastDot !== false) {
            $s = preg_match('/\.\d{1,2}$/', $s) && substr_count($s, '.') === 1 ? $s : str_replace('.', '', $s);
        }
        if (! is_numeric($s)) {
            return null;
        }

        return ['value' => ($neg ? -1 : 1) * (float) $s, 'marker' => $marker];
    }

    /** Tanggal dari berbagai format bank; tanpa tahun (BCA "01/11") memakai $year. */
    public static function parseDate(mixed $v, string $format = 'auto', ?int $year = null): ?CarbonImmutable
    {
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        // Serial tanggal Excel
        if (is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return CarbonImmutable::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $s))->startOfDay();
        }
        $s = preg_replace('/\s+\d{1,2}[:.]\d{2}([:.]\d{2})?.*$/', '', $s);   // buang jam
        $months = ['jan' => 1, 'feb' => 2, 'peb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7,
            'agu' => 8, 'agt' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];
        try {
            if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $s, $m)) {
                return CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3]);
            }
            if (preg_match('/^(\d{1,2})[-\/. ]([A-Za-z]{3,})[-\/. ,]*(\d{2,4})?$/', $s, $m) && isset($months[strtolower(substr($m[2], 0, 3))])) {
                $y = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : ($year ?: (int) date('Y'));

                return CarbonImmutable::create($y < 100 ? 2000 + $y : $y, $months[strtolower(substr($m[2], 0, 3))], (int) $m[1]);
            }
            if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})(?:[-\/.](\d{2,4}))?$/', $s, $m)) {
                [$a, $b] = [(int) $m[1], (int) $m[2]];
                $y = isset($m[3]) ? (int) $m[3] : ($year ?: (int) date('Y'));
                $y = $y < 100 ? 2000 + $y : $y;
                // Bawaan Indonesia: hari/bulan; bulan/hari hanya bila dipilih atau hari > 12 di posisi kedua
                [$d, $mo] = $format === 'm/d/Y' || ($format === 'auto' && $a <= 12 && $b > 12) ? [$b, $a] : [$a, $b];
                if ($mo < 1 || $mo > 12 || $d < 1 || $d > 31) {
                    return null;
                }

                return CarbonImmutable::create($y, $mo, $d);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Baris data → mutasi. Baris yang tanggalnya tidak terbaca (saldo awal, total, catatan kaki) dilewati.
     *
     * @return array{lines: list<array{date: CarbonImmutable, description: string, amount: float, balance: ?float}>, skipped: int}
     */
    public function buildLines(array $rows, array $map, ?int $year = null): array
    {
        $out = [];
        $skipped = 0;
        $col = fn ($row, $key) => isset($map[$key]) && $map[$key] !== null && $map[$key] !== '' ? ($row[(int) $map[$key]] ?? '') : '';
        foreach (array_slice($rows, (int) $map['header_row'] + 1) as $row) {
            $date = self::parseDate($col($row, 'date_col'), $map['date_format'] ?? 'auto', $year);
            if (! $date) {
                $skipped++;
                continue;
            }
            if (($map['amount_mode'] ?? 'single') === 'split') {
                $debit = self::parseAmount($col($row, 'debit_col'))['value'] ?? 0;
                $credit = self::parseAmount($col($row, 'credit_col'))['value'] ?? 0;
                $amount = abs($credit) - abs($debit);
            } else {
                $p = self::parseAmount($col($row, 'amount_col'));
                if (! $p) {
                    $skipped++;
                    continue;
                }
                $type = strtoupper(trim((string) $col($row, 'type_col')));
                $marker = $p['marker'] ?? (in_array($type, ['CR', 'K', 'C', 'KREDIT', 'CREDIT'], true) ? 'CR'
                    : (in_array($type, ['DB', 'D', 'DR', 'DEBIT', 'DEBET'], true) ? 'DB' : null));
                $amount = $marker === 'CR' ? abs($p['value']) : ($marker === 'DB' ? -abs($p['value']) : $p['value']);
            }
            if (round($amount, 2) == 0.0) {
                $skipped++;
                continue;
            }
            $bal = self::parseAmount($col($row, 'balance_col'));
            $out[] = ['date' => $date, 'description' => mb_substr(preg_replace('/\s+/', ' ', trim((string) $col($row, 'desc_col'))), 0, 500),
                'amount' => round($amount, 2), 'balance' => $bal ? round($bal['value'] * ($bal['marker'] === 'DB' ? -1 : 1), 2) : null];
        }

        return ['lines' => $out, 'skipped' => $skipped];
    }

    // ---------------------------------------------------------------- simpan & cocokkan

    public static function mappingKey(BankAccount $account): string
    {
        return "bank_statement_map_{$account->id}";
    }

    public static function savedMapping(BankAccount $account): ?array
    {
        $v = Setting::where('key', self::mappingKey($account))->value('value');
        $m = $v ? json_decode($v, true) : null;

        return is_array($m) ? $m : null;
    }

    /** Simpan baris baru (yang sudah pernah diimport dilewati), lalu cocokkan otomatis. */
    public function import(BankAccount $account, array $lines, string $fileName, ?User $by, array $map = []): BankStatementImport
    {
        if (! $lines) {
            throw new RuntimeException('Tidak ada baris mutasi yang terbaca. Periksa pemetaan kolom & format tanggal.');
        }
        // Bank menampilkan terbaru di atas atau di bawah: urutkan kronologis dengan mempertahankan urutan file per tanggal
        if ($lines[0]['date']->gt(end($lines)['date'])) {
            $lines = array_reverse($lines);
        }

        $import = DB::transaction(function () use ($account, $lines, $fileName, $by) {
            $first = $lines[0];
            $last = end($lines);
            $import = BankStatementImport::create([
                'bank_account_id' => $account->id, 'file_name' => mb_substr($fileName, 0, 191),
                'period_start' => $first['date'], 'period_end' => $last['date'], 'rows_total' => count($lines),
                'opening_balance' => $first['balance'] !== null ? round($first['balance'] - $first['amount'], 2) : null,
                'closing_balance' => $last['balance'], 'created_by' => $by?->id,
            ]);
            $seen = [];
            $new = 0;
            foreach ($lines as $l) {
                $base = $account->id . '|' . $l['date']->toDateString() . '|' . number_format($l['amount'], 2, '.', '') . '|' . mb_strtolower($l['description']);
                $occ = $seen[$base] = ($seen[$base] ?? 0) + 1;   // transaksi kembar di hari yang sama tetap dibedakan
                $fp = hash('sha256', $base . '|' . $occ);
                if (BankStatementLine::where('fingerprint', $fp)->exists()) {
                    continue;
                }
                BankStatementLine::create(['import_id' => $import->id, 'bank_account_id' => $account->id, 'line_date' => $l['date'],
                    'description' => $l['description'], 'amount' => $l['amount'], 'balance' => $l['balance'], 'fingerprint' => $fp]);
                $new++;
            }
            $import->update(['rows_new' => $new]);

            return $import;
        });

        if ($map) {
            Setting::updateOrCreate(['key' => self::mappingKey($account)], ['value' => json_encode($map), 'type' => 'json']);
        }
        $this->autoMatch($account, $import->period_start, $import->period_end);
        \App\Models\ActivityLog::log('bank_statement_imported', "Mutasi {$account->bank_name} {$account->account_name} diimport: {$import->rows_new} baris baru dari {$import->rows_total} ({$fileName}).", $import);

        return $import;
    }

    /** Cocokkan 1:1 baris yang belum cocok: arah & nominal sama, tanggal terdekat dalam ±MATCH_DAYS hari. */
    public function autoMatch(BankAccount $account, $from, $to): int
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->endOfDay();
        $taken = BankStatementLine::whereNotNull('bank_transaction_id')->pluck('bank_transaction_id')->flip();
        $txs = BankTransaction::where('bank_account_id', $account->id)
            ->whereBetween('transaction_date', [$from->subDays(self::MATCH_DAYS), $to->addDays(self::MATCH_DAYS)])
            ->get(['id', 'type', 'amount', 'transaction_date'])
            ->reject(fn ($t) => $taken->has($t->id));
        $n = 0;
        $lines = BankStatementLine::where('bank_account_id', $account->id)->where('status', 'unmatched')
            ->whereBetween('line_date', [$from->toDateString(), $to->toDateString()])->orderBy('line_date')->orderBy('id')->get();
        foreach ($lines as $line) {
            $type = $line->isCredit() ? 'deposit' : 'withdrawal';
            $cand = $txs->filter(fn ($t) => $t->type === $type && abs((float) $t->amount - abs((float) $line->amount)) < 0.005
                    && abs(CarbonImmutable::parse($t->transaction_date)->startOfDay()->diffInDays($line->line_date, false)) <= self::MATCH_DAYS)
                ->sortBy(fn ($t) => abs(CarbonImmutable::parse($t->transaction_date)->startOfDay()->diffInDays($line->line_date, false)) * 1e9 + $t->id)
                ->first();
            if ($cand) {
                $line->update(['status' => 'matched', 'match_type' => 'auto', 'bank_transaction_id' => $cand->id]);
                $txs = $txs->reject(fn ($t) => $t->id === $cand->id);
                $n++;
            }
        }

        return $n;
    }

    /** Ringkasan rekonsiliasi satu rekening untuk rentang tanggal mutasi. */
    public function summary(BankAccount $account, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $lines = BankStatementLine::where('bank_account_id', $account->id)
            ->whereBetween('line_date', [$from->toDateString(), $to->toDateString()])
            ->with('transaction:id,type,amount,transaction_date,description,reference_number')
            ->orderBy('line_date')->orderBy('id')->get();
        $linkedIds = BankStatementLine::whereNotNull('bank_transaction_id')->pluck('bank_transaction_id')->all();
        $onlyApp = BankTransaction::where('bank_account_id', $account->id)
            ->whereBetween('transaction_date', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotIn('id', $linkedIds)->with('category:id,code,name')->orderBy('transaction_date')->get();

        $withBal = $lines->whereNotNull('balance');
        $bankOpening = $withBal->isNotEmpty() ? round((float) $withBal->first()->balance - (float) $withBal->first()->amount, 2) : null;
        $bankClosing = $withBal->isNotEmpty() ? (float) $withBal->last()->balance : null;
        $accounts = collect([$account]);
        $appOpening = $this->statements->balanceAt($accounts, $from->startOfDay());
        $appClosing = $this->statements->balanceAt($accounts, $to->addDay()->startOfDay());

        return [
            'lines' => $lines,
            'matched' => $lines->where('status', 'matched')->values(),
            'only_bank' => $lines->where('status', 'unmatched')->values(),
            'ignored' => $lines->where('status', 'ignored')->values(),
            'only_app' => $onlyApp,
            'bank_in' => round($lines->where('amount', '>', 0)->sum('amount'), 2),
            'bank_out' => round(-$lines->where('amount', '<', 0)->sum('amount'), 2),
            'bank_opening' => $bankOpening, 'bank_closing' => $bankClosing,
            'app_opening' => $appOpening, 'app_closing' => $appClosing,
            'closing_difference' => $bankClosing !== null ? round($bankClosing - $appClosing, 2) : null,
        ];
    }

    /** Kandidat transaksi aplikasi untuk dicocokkan manual dengan satu baris mutasi (arah & nominal sama dulu). */
    public function candidates(BankStatementLine $line)
    {
        $linked = BankStatementLine::whereNotNull('bank_transaction_id')->pluck('bank_transaction_id')->all();

        return BankTransaction::where('bank_account_id', $line->bank_account_id)
            ->where('type', $line->isCredit() ? 'deposit' : 'withdrawal')
            ->whereNotIn('id', $linked)
            ->whereBetween('transaction_date', [$line->line_date->copy()->subDays(31), $line->line_date->copy()->addDays(31)->endOfDay()])
            ->orderByRaw('ABS(amount - ?) ASC', [abs((float) $line->amount)])->orderBy('transaction_date')
            ->limit(15)->get(['id', 'type', 'amount', 'transaction_date', 'description', 'reference_number']);
    }
}
