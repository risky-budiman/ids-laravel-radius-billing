<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Changelog;

class VersionBump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version-bump {type=patch : The type of bump (major, minor, patch)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bump the application version in the VERSION file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $versionFile = base_path('VERSION');

        if (!file_exists($versionFile)) {
            file_put_contents($versionFile, '1.0.0');
            $this->info('Created VERSION file with 1.0.0');
            return;
        }

        $currentVersion = trim(file_get_contents($versionFile));
        
        // Remove 'v' prefix if exists for calculation
        $cleanVersion = ltrim($currentVersion, 'v');
        $parts = explode('.', $cleanVersion);

        if (count($parts) !== 3) {
            $this->error('Invalid version format in VERSION file. Expected x.y.z');
            return;
        }

        $major = (int)$parts[0];
        $minor = (int)$parts[1];
        $patch = (int)$parts[2];

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        $newVersion = "{$major}.{$minor}.{$patch}";
        file_put_contents($versionFile, $newVersion);

        $this->info("Version bumped from {$currentVersion} to {$newVersion}");

        // Create a basic changelog entry
        Changelog::create([
            'version' => $newVersion,
            'title' => "Release {$newVersion}",
            'description' => "Automated version bump ({$type})",
            'type' => 'improvement',
            'release_date' => now()->toDateString(),
        ]);

        $this->info("Changelog entry created for {$newVersion}");
    }
}
