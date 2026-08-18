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
        $currentCommit = $this->runCommand('git rev-parse --short HEAD') ?: 'unknown';
        $branch = $this->runCommand('git rev-parse --abbrev-ref HEAD') ?: 'main';

        // Fetch remote updates
        $fetchOutput = $this->runCommand("git fetch origin {$branch} 2>&1");
        $remoteCommit = $this->runCommand("git rev-parse --short origin/{$branch}") ?: $currentCommit;

        // Check if there are new commits
        $isUpdateAvailable = ($currentCommit !== $remoteCommit) && ($remoteCommit !== 'unknown');

        // Get list of new commits if available
        $newCommits = [];
        if ($isUpdateAvailable) {
            $logOutput = $this->runCommand("git log HEAD..origin/{$branch} --oneline -n 10");
            if ($logOutput) {
                $lines = explode("\n", trim($logOutput));
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $newCommits[] = trim($line);
                    }
                }
            }
        } else {
            // Show last 3 recent commits as current changelog
            $logOutput = $this->runCommand("git log -n 3 --oneline");
            if ($logOutput) {
                $lines = explode("\n", trim($logOutput));
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $newCommits[] = trim($line);
                    }
                }
            }
        }

        return response()->json([
            'success'              => true,
            'current_version'      => $currentVersion,
            'current_commit'       => $currentCommit,
            'remote_commit'        => $remoteCommit,
            'branch'               => $branch,
            'is_update_available'  => $isUpdateAvailable,
            'new_commits'          => $newCommits,
            'status_message'       => $isUpdateAvailable 
                ? "New version available (commit {$remoteCommit})!" 
                : "System is running the latest version.",
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
        $branch = $this->runCommand('git rev-parse --abbrev-ref HEAD') ?: 'main';

        $logs[] = "🚀 [1/6] Initiating System Update on branch '{$branch}'...";

        // Step 1: Git Fetch & Reset
        $logs[] = "📥 [2/6] Pulling latest code from repository...";
        $gitReset = $this->runCommand("git fetch origin {$branch} 2>&1 && git reset --hard origin/{$branch} 2>&1");
        $logs[] = $gitReset ?: "Git repository updated.";

        // Step 2: Database Migration
        $logs[] = "🗄️ [3/6] Running database migrations...";
        try {
            Artisan::call('migrate', ['--force' => true]);
            $logs[] = trim(Artisan::output()) ?: "Migrations are up to date.";
        } catch (\Throwable $e) {
            $logs[] = "Migration note: " . $e->getMessage();
        }

        // Step 3: Refresh Changelog
        $logs[] = "📝 [4/6] Updating version & changelog...";
        try {
            if (Artisan::has('app:generate-changelog')) {
                Artisan::call('app:generate-changelog');
                $logs[] = "Changelog refreshed.";
            }
        } catch (\Throwable $e) {}

        // Step 4: Clear & Rebuild Cache
        $logs[] = "⚡ [5/6] Optimizing configuration and application cache...";
        try {
            Artisan::call('optimize:clear');
            $logs[] = "Cache optimized.";
        } catch (\Throwable $e) {
            $logs[] = "Cache clear note: " . $e->getMessage();
        }

        // Step 5: Reload Horizon & PHP-FPM
        $logs[] = "🌅 [6/6] Reloading Horizon workers & PHP-FPM...";
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
        $newCommit = $this->runCommand('git rev-parse --short HEAD') ?: 'unknown';

        $logs[] = "--------------------------------------------------------";
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
