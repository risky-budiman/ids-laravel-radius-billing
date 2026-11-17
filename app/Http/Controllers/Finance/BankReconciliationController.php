<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Services\Finance\BankReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Keuangan › Rekonsiliasi Bank: import mutasi rekening (CSV/Excel) lalu cocokkan dengan Kas & Bank. */
class BankReconciliationController extends Controller
{
    public function __construct(private BankReconciliationService $recon) {}

    private function accounts()
    {
        return BankAccount::where('is_active', true)->whereIn('type', ['bank', 'payment_gateway'])->orderBy('bank_name')->get();
    }

    public function index(Request $request)
    {
        $accounts = $this->accounts();
        $account = $accounts->firstWhere('id', $request->integer('account')) ?? $accounts->first();
        if (! $account) {
            return view('finance.reconciliation.index', ['accounts' => $accounts, 'account' => null]);
        }
        $imports = BankStatementImport::where('bank_account_id', $account->id)->with('creator:id,name')->latest()->limit(10)->get();
        // Rentang bawaan: rentang import terakhir, atau bulan ini
        $last = $imports->first();
        $from = $request->filled('from') ? CarbonImmutable::parse($request->get('from')) : ($last?->period_start ? CarbonImmutable::parse($last->period_start) : now()->toImmutable()->startOfMonth());
        $to = $request->filled('to') ? CarbonImmutable::parse($request->get('to')) : ($last?->period_end ? CarbonImmutable::parse($last->period_end) : now()->toImmutable());
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        $summary = $this->recon->summary($account, $from, $to);

        return view('finance.reconciliation.index', compact('accounts', 'account', 'imports', 'from', 'to', 'summary'));
    }

    public function upload(Request $request)
    {
        $data = $request->validate(['bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls']);
        $file = $request->file('file');
        $token = (string) Str::uuid();
        $path = $file->storeAs('statements', $token . '.' . strtolower($file->getClientOriginalExtension() ?: 'csv'), 'local');
        session(['statement_pending' => ['token' => $token, 'path' => $path, 'name' => $file->getClientOriginalName(),
            'account' => (int) $data['bank_account_id']]]);

        return redirect()->route('bank-reconciliation.map');
    }

    private function pending(): ?array
    {
        $p = session('statement_pending');

        return is_array($p) && Storage::disk('local')->exists($p['path']) ? $p : null;
    }

    public function map()
    {
        $p = $this->pending();
        if (! $p) {
            return redirect()->route('bank-reconciliation.index')->with('error', 'Unggah file mutasi dulu.');
        }
        $account = BankAccount::findOrFail($p['account']);
        try {
            $rows = $this->recon->readRows(Storage::disk('local')->path($p['path']), $p['name']);
        } catch (\Throwable $e) {
            return redirect()->route('bank-reconciliation.index', ['account' => $account->id])->with('error', 'File tidak bisa dibaca: ' . $e->getMessage());
        }
        $map = old() ? old() : (BankReconciliationService::savedMapping($account) ?? $this->recon->guessMapping($rows));
        $width = max(array_map('count', array_slice($rows, 0, 40) ?: [[]]));
        $preview = $this->recon->buildLines($rows, $map + ['header_row' => 0], (int) ($map['year'] ?? date('Y')));

        return view('finance.reconciliation.map', ['account' => $account, 'file' => $p['name'], 'rows' => array_slice($rows, 0, 40),
            'width' => $width, 'map' => $map, 'preview' => array_slice($preview['lines'], 0, 8), 'previewCount' => count($preview['lines']),
            'skipped' => $preview['skipped'], 'totalRows' => count($rows)]);
    }

    public function confirm(Request $request)
    {
        $p = $this->pending();
        if (! $p) {
            return redirect()->route('bank-reconciliation.index')->with('error', 'File mutasi kedaluwarsa, unggah ulang.');
        }
        $col = 'nullable|integer|min:0|max:100';
        $map = $request->validate([
            'header_row' => 'required|integer|min:0', 'date_col' => 'required|integer|min:0|max:100', 'desc_col' => $col,
            'amount_mode' => 'required|in:single,split', 'amount_col' => 'required_if:amount_mode,single|' . $col,
            'debit_col' => 'required_if:amount_mode,split|' . $col, 'credit_col' => 'required_if:amount_mode,split|' . $col,
            'type_col' => $col, 'balance_col' => $col, 'date_format' => 'required|in:auto,d/m/Y,m/d/Y', 'year' => 'nullable|integer|min:2000|max:2100',
        ]);
        $account = BankAccount::findOrFail($p['account']);
        $rows = $this->recon->readRows(Storage::disk('local')->path($p['path']), $p['name']);
        $built = $this->recon->buildLines($rows, $map, (int) ($map['year'] ?? date('Y')));

        if ($request->input('action') === 'preview') {
            return redirect()->route('bank-reconciliation.map')->withInput();
        }
        try {
            $import = $this->recon->import($account, $built['lines'], $p['name'], $request->user(), array_diff_key($map, ['year' => 1]));
        } catch (\RuntimeException $e) {
            return redirect()->route('bank-reconciliation.map')->withInput()->with('error', $e->getMessage());
        }
        Storage::disk('local')->delete($p['path']);
        session()->forget('statement_pending');

        return redirect()->route('bank-reconciliation.index', ['account' => $account->id, 'from' => $import->period_start->toDateString(), 'to' => $import->period_end->toDateString()])
            ->with('success', "{$import->rows_new} baris mutasi baru diimport dari {$import->rows_total}"
                . ($import->rows_new < $import->rows_total ? ' (' . ($import->rows_total - $import->rows_new) . ' sudah pernah diimport)' : '')
                . ($built['skipped'] ? ", {$built['skipped']} baris bukan transaksi dilewati" : '') . '. Pencocokan otomatis sudah dijalankan.');
    }

    public function candidates(BankStatementLine $line)
    {
        return response()->json($this->recon->candidates($line)->map(fn ($t) => ['id' => $t->id,
            'label' => $t->transaction_date->format('d/m/Y') . ' · Rp ' . number_format((float) $t->amount, 0, ',', '.') . ' · ' . Str::limit((string) $t->description, 60)]));
    }

    public function match(Request $request, BankStatementLine $line)
    {
        $tx = BankTransaction::findOrFail($request->validate(['bank_transaction_id' => 'required|integer'])['bank_transaction_id']);
        abort_unless($tx->bank_account_id === $line->bank_account_id, 422, 'Transaksi dari rekening lain.');
        if (BankStatementLine::where('bank_transaction_id', $tx->id)->whereKeyNot($line->id)->exists()) {
            return back()->with('error', 'Transaksi itu sudah dicocokkan dengan baris mutasi lain.');
        }
        $line->update(['status' => 'matched', 'match_type' => 'manual', 'bank_transaction_id' => $tx->id, 'handled_by' => $request->user()->id]);
        ActivityLog::log('bank_statement_matched', "Mutasi {$line->line_date->format('d/m/Y')} Rp " . number_format(abs((float) $line->amount), 0, ',', '.') . " dicocokkan manual dengan transaksi #{$tx->id}.", $line);

        return back()->with('success', 'Mutasi dicocokkan.');
    }

    public function unmatch(BankStatementLine $line)
    {
        $line->update(['status' => 'unmatched', 'match_type' => null, 'bank_transaction_id' => null, 'note' => null]);

        return back()->with('success', 'Pencocokan dibatalkan.');
    }

    public function ignore(Request $request, BankStatementLine $line)
    {
        $note = $request->validate(['note' => 'required|string|max:300'])['note'];
        $line->update(['status' => 'ignored', 'note' => $note, 'handled_by' => $request->user()->id]);
        ActivityLog::log('bank_statement_ignored', "Mutasi {$line->line_date->format('d/m/Y')} Rp " . number_format(abs((float) $line->amount), 0, ',', '.') . " diabaikan: {$note}", $line);

        return back()->with('success', 'Mutasi ditandai diabaikan.');
    }

    /** Catat mutasi bank sebagai pemasukan / pengeluaran di aplikasi (form terisi), lalu cocok otomatis. */
    public function record(BankStatementLine $line)
    {
        $route = $line->isCredit() ? 'bank-transactions.income' : 'bank-transactions.expense';

        return redirect()->route($route)->withInput([
            'bank_account_id' => $line->bank_account_id,
            'amount' => number_format(abs((float) $line->amount), 2, '.', ''),
            'transaction_date' => $line->line_date->format('Y-m-d') . 'T12:00',
            'description' => Str::limit((string) $line->description, 250, ''),
            'reference_number' => 'MUTASI-' . $line->id,
        ]);
    }

    public function rematch(Request $request)
    {
        $data = $request->validate(['account' => 'required|integer|exists:bank_accounts,id', 'from' => 'required|date', 'to' => 'required|date']);
        $n = $this->recon->autoMatch(BankAccount::findOrFail($data['account']), $data['from'], $data['to']);

        return back()->with('success', "Pencocokan otomatis: {$n} mutasi baru cocok.");
    }

    public function destroyImport(BankStatementImport $import)
    {
        $label = "{$import->file_name} ({$import->rows_new} baris)";
        $import->delete();
        ActivityLog::log('bank_statement_import_deleted', "Import mutasi dihapus: {$label}.");

        return back()->with('success', "Import {$label} dihapus beserta baris mutasinya.");
    }
}
