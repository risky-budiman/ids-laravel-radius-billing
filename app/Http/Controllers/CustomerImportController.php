<?php

namespace App\Http\Controllers;

use App\Services\CustomerImportService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Exception;

class CustomerImportController extends Controller
{
    protected CustomerImportService $importService;

    public function __construct(CustomerImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show import form & guidelines
     */
    /** File dengan baris data lebih dari ini diproses di latar belakang (halaman tidak menunggu). */
    public static int $backgroundRows = 300;

    public function index(Request $request)
    {
        $run = null;
        if ($id = $request->query('run')) {
            $run = \App\Models\ImportRun::find($id);
            abort_unless($run && ($run->user_id === auth()->id() || auth()->user()->isAdministrator()), 404);
        }

        return view('customers.import', ['run' => $run]);
    }

    /** Status proses latar belakang (dipantau halaman impor). */
    public function status(\App\Models\ImportRun $run)
    {
        abort_unless($run->user_id === auth()->id() || auth()->user()->isAdministrator(), 404);

        return response()->json(['status' => $run->status, 'percent' => $run->percent(), 'processed' => $run->processed_rows,
            'total' => $run->total_rows, 'error' => $run->error, 'started' => (bool) $run->started_at]);
    }

    /** Antrekan pratinjau/impor file besar; halaman impor memantau kemajuannya. */
    private function queueRun(string $mode, string $path, string $name, string $duplicateAction, int $rows, ?string $id = null)
    {
        $run = new \App\Models\ImportRun([
            'user_id' => auth()->id(), 'mode' => $mode, 'file_name' => $name, 'path' => $path,
            'duplicate_action' => $duplicateAction, 'total_rows' => $rows,
        ]);
        if ($id) {
            $run->id = $id;   // pratinjau: id = token sesi, agar "Impor sekarang" melanjutkan file yang sama
        }
        $run->save();
        \App\Jobs\RunCustomerImport::dispatch($run->id);

        return redirect()->route('customers.import.index', ['run' => $run->id])
            ->with('success', ($mode === 'preview' ? 'Pratinjau' : 'Impor') . " {$rows} baris berjalan di latar belakang. Halaman ini boleh ditutup; hasilnya tetap tersimpan.");
    }

    /**
     * Download Excel template with dropdown selections and sample rows
     */
    public function downloadTemplate()
    {
        $spreadsheet = $this->importService->generateTemplateSpreadsheet();
        $fileName = 'template_import_pelanggan_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Process uploaded Excel or CSV file
     */
    public function store(Request $request)
    {
        $request->validate([
            // file tidak perlu diunggah ulang saat melanjutkan dari pratinjau (token)
            'file' => 'required_without:token|file|mimes:xlsx,xls,csv,txt|max:20480', // max 20MB
            'token' => 'nullable|string|size:36',
            'duplicate_action' => 'required|in:skip,update',
            'mode' => 'nullable|in:preview,import',
        ], [
            'file.required_without' => 'Silakan pilih file spreadsheet yang ingin diunggah.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 20 MB.',
        ]);

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $options = ['duplicate_action' => $request->input('duplicate_action', 'skip')];

        try {
            // 1) Lanjutkan impor dari pratinjau: file yang sama, tanpa unggah ulang
            if ($request->filled('token')) {
                $pending = session('import_pending');
                if (! $pending || $pending['token'] !== $request->input('token') || ! $disk->exists($pending['path'])) {
                    return redirect()->route('customers.import.index')->with('error', 'Pratinjau sudah kedaluwarsa. Unggah file lagi.');
                }
                session()->forget('import_pending');
                $rows = $this->importService->countDataRows($disk->path($pending['path']));
                if ($rows > self::$backgroundRows) {
                    return $this->queueRun('import', $pending['path'], $pending['name'], $options['duplicate_action'], $rows);
                }
                $result = $this->importService->importFile($disk->path($pending['path']), $options);
                $disk->delete($pending['path']);

                return $this->finishImport($result, $pending['name']);
            }

            $file = $request->file('file');

            // 2) Pratinjau (dry-run): tidak ada yang disimpan; file disimpan sementara untuk dilanjutkan
            if ($request->input('mode') === 'preview') {
                $old = session('import_pending');
                if ($old) {
                    $disk->delete($old['path']);
                }
                $token = (string) \Illuminate\Support\Str::uuid();
                $path = $file->storeAs('imports', $token . '.' . ($file->getClientOriginalExtension() ?: 'xlsx'), 'local');
                session(['import_pending' => ['token' => $token, 'path' => $path, 'name' => $file->getClientOriginalName(),
                    'duplicate_action' => $options['duplicate_action']]]);

                $rows = $this->importService->countDataRows($disk->path($path));
                if ($rows > self::$backgroundRows) {
                    // id proses = token, sehingga "Impor sekarang" dari hasil pratinjau memakai file yang sama
                    return $this->queueRun('preview', $path, $file->getClientOriginalName(), $options['duplicate_action'], $rows, $token);
                }
                $result = $this->importService->importFile($disk->path($path), $options + ['dry_run' => true]);

                return redirect()->route('customers.import.index')
                    ->with('import_result', $result + ['token' => $token, 'file_name' => $file->getClientOriginalName(), 'duplicate_action' => $options['duplicate_action']]);
            }

            // 3) Impor langsung
            $rows = $this->importService->countDataRows($file->getRealPath());
            if ($rows > self::$backgroundRows) {
                $path = $file->storeAs('imports', \Illuminate\Support\Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'xlsx'), 'local');

                return $this->queueRun('import', $path, $file->getClientOriginalName(), $options['duplicate_action'], $rows);
            }

            return $this->finishImport($this->importService->importFile($file->getRealPath(), $options), $file->getClientOriginalName());
        } catch (Exception $e) {
            return redirect()->route('customers.import.index')
                ->with('error', "Gagal memproses file import: " . $e->getMessage());
        }
    }

    private function finishImport(array $result, string $fileName)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'customers_imported',
            'description' => "Impor pelanggan dari file '{$fileName}': {$result['success']} dibuat, {$result['updated']} diperbarui, {$result['skipped']} dilewati.",
        ]);

        return redirect()->route('customers.import.index')
            ->with('import_result', $result)
            ->with('success', "Proses impor selesai! {$result['success']} pelanggan baru berhasil ditambahkan, {$result['updated']} diperbarui, dan {$result['skipped']} dilewati.");
    }

    /**
     * Export customers data to Excel (compatible with import template format)
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'package_id', 'billing_type', 'search']);
        // Password PPPoE hanya bila diminta khusus (dulu selalu ikut terekspor)
        $withPasswords = $request->boolean('with_passwords');
        $csv = $request->get('format') === 'csv';

        $filterSuffix = '';
        if (!empty($filters['status'])) {
            $filterSuffix .= '_' . preg_replace('/[^a-z_]/', '', (string) $filters['status']);
        }

        $fileName = 'export_pelanggan' . $filterSuffix . '_' . date('Ymd_His') . ($csv ? '.csv' : '.xlsx');

        if ($csv) {
            ActivityLog::create(['user_id' => auth()->id(), 'action' => 'customers_exported',
                'description' => "Export data pelanggan ke file '{$fileName}'" . ($withPasswords ? ' TERMASUK password PPPoE.' : ' (tanpa password).')]);

            // CSV dialirkan baris per baris: memori tetap kecil untuk ribuan pelanggan
            return response()->streamDownload(fn () => $this->importService->streamCustomersCsv($filters, $withPasswords),
                $fileName, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'max-age=0']);
        }

        @set_time_limit(300);
        // Excel menyimpan semua sel di memori (~50 KB per pelanggan): beri ruang; data sangat besar → CSV
        $limit = trim((string) ini_get('memory_limit'));
        $bytes = (int) $limit * (['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10][strtolower(substr($limit, -1))] ?? 1);
        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        $spreadsheet = $this->importService->exportCustomersSpreadsheet($filters, $withPasswords);

        // Log activity
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'customers_exported',
            'description' => "Export data pelanggan ke file '{$fileName}'" . ($withPasswords ? ' TERMASUK password PPPoE.' : ' (tanpa password).'),
        ]);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
