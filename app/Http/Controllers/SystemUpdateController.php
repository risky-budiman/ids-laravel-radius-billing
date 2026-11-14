<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemUpdateController extends Controller
{
    /**
     * Check for latest system updates from Git repository.
     */
    public function check()
    {
        $currentVersion = app_version();
        
        // 1. Get current local commit hash
        $currentCommitLong = $this->runCommand('git -c safe.directory=* rev-parse HEAD');
        $currentCommit = $currentCommitLong ? substr(trim($currentCommitLong), 0, 7) : 'unknown';
        $branch = $this->runCommand('git -c safe.directory=* rev-parse --abbrev-ref HEAD') ?: 'main';

        // 2. Query remote commit using ls-remote (fast, doesn't need file locks or write permissions)
        $remoteOutput = $this->runCommand("git -c safe.directory=* ls-remote origin refs/heads/{$branch}");
        $remoteCommitLong = null;
        if ($remoteOutput && preg_match('/^([a-f0-9]{40})/i', trim($remoteOutput), $m)) {
            $remoteCommitLong = $m[1];
        }

        // Fallback: try git fetch and rev-parse if ls-remote didn't return
        if (!$remoteCommitLong) {
            $this->runCommand("git -c safe.directory=* fetch origin {$branch} 2>&1");
            $remoteCommitLong = $this->runCommand("git -c safe.directory=* rev-parse origin/{$branch}");
        }

        $remoteCommit = $remoteCommitLong ? substr(trim($remoteCommitLong), 0, 7) : $currentCommit;

        // 3. Compare commits
        $isUpdateAvailable = ($currentCommit !== 'unknown') 
            && ($remoteCommitLong !== null) 
            && (trim($currentCommitLong) !== trim($remoteCommitLong));

        // 4. Fetch list of new commits if update is available
        $newCommits = [];
        if ($isUpdateAvailable) {
            $this->runCommand("git -c safe.directory=* fetch origin {$branch} 2>&1");
            $logOutput = $this->runCommand("git -c safe.directory=* log HEAD..origin/{$branch} --oneline -n 10");
            if ($logOutput) {
                $lines = explode("\n", trim($logOutput));
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $newCommits[] = trim($line);
                    }
                }
            }
        } else {
            // Show last 5 recent commits as current changelog
            $logOutput = $this->runCommand("git -c safe.directory=* log -n 5 --oneline");
            if ($logOutput) {
                $lines = explode("\n", trim($logOutput));
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $newCommits[] = trim($line);
                    }
                }
            }
        }

        $cliFiles = $isUpdateAvailable ? $this->filesNeedingCli($branch) : [];

        return response()->json([
            'success'              => true,
            'requires_cli'         => (bool) $cliFiles,
            'cli_files'            => $cliFiles,
            'current_version'      => $currentVersion,
            'current_commit'       => $currentCommit,
            'remote_commit'        => $remoteCommit,
            'branch'               => $branch,
            'is_update_available'  => $isUpdateAvailable,
            'new_commits'          => $newCommits,
            'status_message'       => $isUpdateAvailable
                ? ($cliFiles
                    ? "Update tersedia ({$remoteCommit}), tetapi mengubah dependensi/aset — jalankan ./update.sh di server (SSH)."
                    : "New update available! (Remote commit: {$remoteCommit})")
                : "System is up to date (commit {$currentCommit}).",
            'checked_at'           => now()->format('H:i:s'),
        ]);
    }

    /**
     * Execute system update directly from Web UI.
     */
    public function run()
    {
        $startTime = microtime(true);
        $logs = [];
        $branch = $this->runCommand('git -c safe.directory=* rev-parse --abbrev-ref HEAD') ?: 'main';
        if ($branch === 'HEAD' || ! preg_match('/^[A-Za-z0-9._\/-]+$/', $branch)) {
            return response()->json(['success' => false, 'message' => "Branch tidak valid untuk update otomatis ({$branch}). Jalankan ./update.sh di server."], 422);
        }

        // Update dari web tidak menjalankan composer/npm (tidak ada izin & waktu request terbatas).
        // Bila versi baru mengubah dependensi atau aset, menerapkannya dari sini membuat situs
        // error 500 (library hilang) atau tampil rusak (CSS/JS basi) — arahkan ke update.sh.
        $this->runCommand("git -c safe.directory=* fetch origin {$branch} 2>&1");
        if ($cliFiles = $this->filesNeedingCli($branch)) {
            Log::warning('Web update ditolak: perubahan membutuhkan composer/npm.', ['files' => $cliFiles]);

            return response()->json([
                'success' => false,
                'requires_cli' => true,
                'message' => 'Update ini mengubah dependensi/aset (' . implode(', ', array_slice($cliFiles, 0, 5)) . (count($cliFiles) > 5 ? ', …' : '')
                    . '). Jalankan ./update.sh di server lewat SSH agar composer install & npm run build ikut dijalankan.',
            ], 409);
        }

        $logs[] = "🚀 [1/5] Initiating System Update on branch '{$branch}'...";

        // Step 1: Git Fetch & Reset with safe.directory
        $logs[] = "📥 [2/5] Fetching and resetting to latest code from GitHub...";
        $gitReset = $this->runCommand("git -c safe.directory=* fetch origin {$branch} 2>&1 && git -c safe.directory=* reset --hard origin/{$branch} 2>&1");
        $logs[] = $gitReset ?: "Git repository updated.";

        // Step 2: Database Migration
        $logs[] = "🗄️ [3/5] Running database migrations...";
        $migrationError = null;
        try {
            $exit = Artisan::call('migrate', ['--force' => true]);
            $logs[] = trim(Artisan::output()) ?: "Database schema is up to date.";
            if ($exit !== 0) {
                $migrationError = 'migrate exit code ' . $exit;
            }
        } catch (\Throwable $e) {
            $migrationError = $e->getMessage();
            $logs[] = "❌ Migration FAILED: " . $e->getMessage();
        }

        // Step 3: Refresh Changelog
        try {
            if (Artisan::has('app:generate-changelog')) {
                Artisan::call('app:generate-changelog');
                $changelogOutput = trim(Artisan::output());
                $logs[] = $changelogOutput ?: "Changelog generated.";
            }
        } catch (\Throwable $e) {
            $logs[] = "Changelog note: " . $e->getMessage();
        }

        // Step 4: Clear & Rebuild Cache
        $logs[] = "⚡ [4/5] Clearing and optimizing application caches...";
        try {
            Artisan::call('optimize:clear');
            $logs[] = "Application caches refreshed.";
        } catch (\Throwable $e) {
            $logs[] = "Cache note: " . $e->getMessage();
        }

        // Step 5: Reload Horizon & PHP-FPM
        $logs[] = "🌅 [5/5] Reloading workers & PHP-FPM...";
        try {
            if (Artisan::has('horizon:terminate')) {
                Artisan::call('horizon:terminate');
            }
        } catch (\Throwable $e) {}

        // Try reloading PHP-FPM if permitted on Linux
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            @shell_exec('sudo systemctl reload php8.3-fpm 2>/dev/null || sudo systemctl restart php8.3-fpm 2>/dev/null');
        }

        $duration = round(microtime(true) - $startTime, 2);
        $newVersion = app_version();
        $newCommitLong = $this->runCommand('git -c safe.directory=* rev-parse HEAD');
        $newCommit = $newCommitLong ? substr(trim($newCommitLong), 0, 7) : 'unknown';

        $logs[] = "--------------------------------------------------------";
        if ($migrationError) {
            $logs[] = "❌ UPDATE SELESAI DENGAN ERROR: migrasi database gagal. Periksa log & jalankan php artisan migrate di server.";
            Log::error("Web update {$newCommit}: migrasi gagal — {$migrationError}");

            return response()->json([
                'success' => false,
                'message' => 'Kode sudah diperbarui, tetapi migrasi database GAGAL: ' . $migrationError,
                'new_commit' => $newCommit,
                'logs' => implode(PHP_EOL, $logs),
            ], 500);
        }
        $logs[] = "✅ SYSTEM UPDATE COMPLETED in {$duration}s!";
        $logs[] = "📦 Active Version: {$newVersion} (commit {$newCommit})";

        Log::info("System updated to {$newVersion} ({$newCommit}) via Web UI in {$duration}s.");

        return response()->json([
            'success'     => true,
            'message'     => "System successfully updated to {$newVersion} ({$newCommit})",
            'new_version' => $newVersion,
            'new_commit'  => $newCommit,
            'duration'    => $duration,
            'logs'        => implode("\n", $logs),
        ]);
    }

    /**
     * File yang berubah antara HEAD dan origin/{branch} yang membutuhkan composer/npm.
     */
    protected function filesNeedingCli(string $branch): array
    {
        $diff = $this->runCommand("git -c safe.directory=* diff --name-only HEAD origin/{$branch}") ?? '';
        $patterns = '#^(composer\.(json|lock)|package(-lock)?\.json|vite\.config\.js|tailwind\.config\.js|postcss\.config\.js|resources/(css|js)/)#';

        $files = preg_split('/\R/', $diff) ?: [];

        return array_values(array_filter(array_map('trim', $files), fn ($f) => $f !== '' && preg_match($patterns, $f)));
    }

    /**
     * Helper to run CLI shell command safely.
     */
    protected function runCommand(string $command): ?string
    {
        try {
            $base = base_path();
            $fullCommand = "cd " . escapeshellarg($base) . " && {$command}";
            $output = shell_exec($fullCommand);
            return $output !== null ? trim($output) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
