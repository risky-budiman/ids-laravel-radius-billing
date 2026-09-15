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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480', // max 20MB
            'duplicate_action' => 'required|in:skip,update',
        ], [
            'file.required' => 'Silakan pilih file spreadsheet yang ingin diunggah.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 20 MB.',
        ]);

        try {
            $file = $request->file('file');
            $options = [
                'duplicate_action' => $request->input('duplicate_action', 'skip'),
            ];

            $result = $this->importService->importFile($file->getRealPath(), $options);

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'customers_imported',
                'description' => "Impor pelanggan dari file '{$file->getClientOriginalName()}': {$result['success']} dibuat, {$result['updated']} diperbarui, {$result['skipped']} dilewati.",
            ]);

            return redirect()->route('customers.import.index')
                ->with('import_result', $result)
                ->with('success', "Proses impor selesai! {$result['success']} pelanggan baru berhasil ditambahkan, {$result['updated']} diperbarui, dan {$result['skipped']} dilewati.");
        } catch (Exception $e) {
            return redirect()->route('customers.import.index')
                ->with('error', "Gagal memproses file import: " . $e->getMessage());
        }
    }

    /**
     * Export customers data to Excel (compatible with import template format)
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'package_id', 'billing_type', 'search']);

        $spreadsheet = $this->importService->exportCustomersSpreadsheet($filters);
        
        $filterSuffix = '';
        if (!empty($filters['status'])) {
            $filterSuffix .= '_' . $filters['status'];
        }
        
        $fileName = 'export_pelanggan' . $filterSuffix . '_' . date('Ymd_His') . '.xlsx';

        // Log activity
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'customers_exported',
            'description' => "Export data pelanggan ke file '{$fileName}'.",
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
