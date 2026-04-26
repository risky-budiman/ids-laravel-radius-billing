<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ServerLogController extends Controller
{
    /**
     * Display the system/server logs.
     */
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = '';
        $fileSize = 0;

        if (File::exists($logPath)) {
            $fileSize = File::size($logPath);
            // Membaca maksimal 1000 baris terakhir agar browser tidak hang (blank)
            $logs = implode("", $this->tailFile($logPath, 1000));
            // Menghapus karakter non-UTF8 yang bisa membuat halaman menjadi blank
            $logs = mb_convert_encoding($logs, 'UTF-8', 'UTF-8');
        } else {
            $logs = "Log file not found or is empty.";
        }

        // Format file size for display
        $formattedSize = $this->formatBytes($fileSize);

        return view('server-logs.index', compact('logs', 'formattedSize'));
    }

    /**
     * Clear the log file content.
     */
    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
            return redirect()->route('server-logs.index')->with('success', 'Server log has been cleared successfully.');
        }

        return redirect()->route('server-logs.index')->with('error', 'Failed to clear log file.');
    }

    /**
     * Efficiently read the last N lines of a file
     */
    private function tailFile($filepath, $lines = 100) {
        $f = @fopen($filepath, "rb");
        if ($f === false) return [];
        $cursor = -1;
        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        // Trim trailing newline chars of the file
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        $lineCounter = 0;
        while ($lineCounter < $lines && fseek($f, $cursor--, SEEK_END) !== -1) {
            $char = fgetc($f);
            if ($char === "\n") {
                $lineCounter++;
            }
        }
        fseek($f, $cursor + 2, SEEK_END);
        $output = [];
        while (!feof($f)) {
            $output[] = fgets($f);
        }
        fclose($f);
        return $output;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
