<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Changelog;

class BumpVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bump-version {type=patch : Type of bump: patch, minor, major, or explicit version number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increment application version (MAJOR.MINOR.PATCH) in VERSION file and update Changelog';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $versionFile = base_path('VERSION');
        $currentVersion = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : '1.0.0';
        $type = strtolower($this->argument('type'));

        $parts = explode('.', ltrim($currentVersion, 'v'));
        $major = (int)($parts[0] ?? 1);
        $minor = (int)($parts[1] ?? 0);
        $patch = (int)($parts[2] ?? 0);

        if ($type === 'major') {
            $major++;
            $minor = 0;
            $patch = 0;
            $newVersion = "{$major}.{$minor}.{$patch}";
        } elseif ($type === 'minor') {
            $minor++;
            $patch = 0;
            $newVersion = "{$major}.{$minor}.{$patch}";
        } elseif ($type === 'patch') {
            $patch++;
            $newVersion = "{$major}.{$minor}.{$patch}";
        } elseif (preg_match('/^\d+\.\d+\.\d+$/', $type)) {
            $newVersion = $type;
        } else {
            $this->error("Invalid bump type '{$type}'. Use: patch, minor, major, or a specific version like 1.6.0");
            return 1;
        }

        file_put_contents($versionFile, $newVersion);
        $this->info("🚀 Version bumped: v{$currentVersion} -> v{$newVersion}");

        // Automatically update changelog
        $this->call('app:generate-changelog');

        return 0;
    }
}
