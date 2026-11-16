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
    public function index()
    {
        return view('customers.import');
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
                $result = $this->importService->importFile($disk->path($pending['path']), $options);
                $disk->delete($pending['path']);
                session()->forget('import_pending');

                return $this->finishImport($result, $pending['name']);
            }

            $file = $request->file('file');

            // 2) Pratinjau (dry-run): tidak ada yang disimpan; file disimpan sementara untuk dilanjutkan
            if ($request->input('mode') === 'preview') {
                $result = $this->importService->importFile($file->getRealPath(), $options + ['dry_run' => true]);
                $old = session('import_pending');
                if ($old) {
                    $disk->delete($old['path']);
                }
                $token = (string) \Illuminate\Support\Str::uuid();
                $path = $file->storeAs('imports', $token . '.' . ($file->getClientOriginalExtension() ?: 'xlsx'), 'local');
                session(['import_pending' => ['token' => $token, 'path' => $path, 'name' => $file->getClientOriginalName(),
                    'duplicate_action' => $options['duplicate_action']]]);

                return redirect()->route('customers.import.index')
                    ->with('import_result', $result + ['token' => $token, 'file_name' => $file->getClientOriginalName(), 'duplicate_action' => $options['duplicate_action']]);
            }

            // 3) Impor langsung
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

        $spreadsheet = $this->importService->exportCustomersSpreadsheet($filters, $withPasswords);
        
        $filterSuffix = '';
        if (!empty($filters['status'])) {
            $filterSuffix .= '_' . $filters['status'];
        }
        
        $fileName = 'export_pelanggan' . $filterSuffix . '_' . date('Ymd_His') . '.xlsx';

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
