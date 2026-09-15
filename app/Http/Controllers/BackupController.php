<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Exception;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display backup & restore dashboard
     */
    public function index()
    {
        $modules = $this->backupService->getModules();
        $localBackups = $this->backupService->listLocalBackups();

        return view('backup.index', compact('modules', 'localBackups'));
    }

    /**
     * Create a new backup file
     */
    public function store(Request $request)
    {
        $request->validate([
            'modules' => 'required|array|min:1',
            'notes' => 'nullable|string|max:255',
            'custom_password' => 'nullable|string|min:6',
        ], [
            'modules.required' => 'Silakan pilih minimal satu modul yang ingin dicadangkan.',
            'custom_password.min' => 'Password enkripsi minimal 6 karakter jika diisi.',
        ]);

        try {
            $creator = auth()->user()->name ?? 'Administrator';
            $result = $this->backupService->createBackup(
                $request->input('modules', []),
                $request->input('notes'),
                $request->input('custom_password'),
                $creator
            );

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_created',
                'description' => "Membuat cadangan data: {$result['filename']} (" . count($result['metadata']['tables']) . " tabel, {$result['metadata']['total_records']} baris data)",
            ]);

            return redirect()->route('backup.index')
                ->with('success', "Cadangan data '{$result['filename']}' berhasil dibuat! Ukuran: " . $this->backupService->formatSize($result['size']));
        } catch (Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', "Gagal membuat cadangan data: " . $e->getMessage());
        }
    }

    /**
     * Download backup file securely
     */
    public function download(string $filename)
    {
        $path = $this->backupService->getBackupPath($filename);

        if (!$path || !File::exists($path)) {
            return redirect()->route('backup.index')->with('error', 'File cadangan tidak ditemukan.');
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'backup_downloaded',
            'description' => "Mengunduh file cadangan: {$filename}",
        ]);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Delete backup file
     */
    public function destroy(string $filename)
    {
        $deleted = $this->backupService->deleteBackup($filename);

        if ($deleted) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_deleted',
                'description' => "Menghapus file cadangan: {$filename}",
            ]);

            return redirect()->route('backup.index')->with('success', "File cadangan '{$filename}' berhasil dihapus.");
        }

        return redirect()->route('backup.index')->with('error', 'Gagal menghapus file cadangan.');
    }

    /**
     * Inspect uploaded or existing file before restoring (AJAX API)
     */
    public function inspect(Request $request)
    {
        try {
            $filePath = null;
            $tempFile = false;

            if ($request->hasFile('backup_file')) {
                $file = $request->file('backup_file');
                $filePath = $file->getRealPath();
            } elseif ($request->filled('filename')) {
                $filePath = $this->backupService->getBackupPath($request->input('filename'));
            }

            if (!$filePath || !File::exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'File tidak ditemukan.'], 404);
            }

            $password = $request->input('password');
            $inspection = $this->backupService->inspectBackup($filePath, $password);

            return response()->json([
                'success' => true,
                'data' => $inspection,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore database from backup file
     */
    public function restore(Request $request)
    {
        $request->validate([
            'restore_source' => 'required|in:upload,local',
            'backup_file' => 'required_if:restore_source,upload|file|max:512000', // max 500MB
            'local_filename' => 'required_if:restore_source,local|string',
            'password' => 'nullable|string',
            'confirm_restore' => 'required|accepted',
        ], [
            'backup_file.required_if' => 'File cadangan wajib diunggah jika memilih sumber upload.',
            'local_filename.required_if' => 'Pilih salah satu file cadangan dari server.',
            'confirm_restore.accepted' => 'Anda harus mencentang persetujuan konfirmasi pemulihan.',
        ]);

        $filePath = null;
        $isTemp = false;

        try {
            if ($request->restore_source === 'upload') {
                $uploaded = $request->file('backup_file');
                $filePath = $uploaded->getRealPath();
            } else {
                $filePath = $this->backupService->getBackupPath($request->input('local_filename'));
            }

            if (!$filePath || !File::exists($filePath)) {
                throw new Exception("File cadangan tidak valid atau tidak ditemukan.");
            }

            $password = $request->input('password');
            $wipe = $request->boolean('wipe_existing', true);

            // Execute restore
            $result = $this->backupService->restoreBackup($filePath, [], $password, $wipe);

            $sourceLabel = $request->restore_source === 'upload' ? 'file upload' : $request->local_filename;
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'backup_restored',
                'description' => "Memulihkan data dari cadangan ({$sourceLabel}): {$result['total_rows_restored']} baris data pada " . count($result['restored_tables']) . " tabel.",
            ]);

            return redirect()->route('backup.index')->with('success', "Pemulihan database berhasil! Total {$result['total_rows_restored']} data pada " . count($result['restored_tables']) . " tabel telah dipulihkan.");
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', "Pemulihan gagal: " . $e->getMessage());
        }
    }
}
