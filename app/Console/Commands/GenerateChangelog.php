<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Changelog;
use Illuminate\Support\Facades\DB;

class GenerateChangelog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-changelog {--dry-run : Only show changes without writing} {--message= : Include this message as a manual entry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CHANGELOG.md and update database from git logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $version = app_version();
        $formattedVersion = str_starts_with($version, 'v') ? $version : "v{$version}";
        $this->info("Generating changelog for version {$formattedVersion} from git logs...");

        $base = base_path();

        // 1. Fetch recent git log commits
        $gitCommand = "cd " . escapeshellarg($base) . " && git -c safe.directory=* log -n 15 --pretty=format:\"%h|%s|%an|%ad\" --date=short 2>&1";
        $output = shell_exec($gitCommand);

        $lines = $output ? explode("\n", trim($output)) : [];
        $newChanges = [];

        // Add manual message if provided
        if ($this->option('message')) {
            $cleanMsg = trim(str_ireplace(['#major', '#minor', '#patch'], '', $this->option('message')));
            $newChanges[] = [
                'hash' => 'HEAD',
                'subject' => $cleanMsg,
                'author' => 'System',
                'date' => now()->toDateString()
            ];
        }

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 2) continue;

            $hash = $parts[0];
            $subject = trim(str_ireplace(['#major', '#minor', '#patch'], '', $parts[1]));
            $author = $parts[2] ?? 'Developer';
            $date = $parts[3] ?? now()->toDateString();

            // Skip merge commits and automated version tags
            if (str_starts_with($subject, 'Merge branch')) continue;
            if (str_starts_with($subject, 'fatal:') || str_starts_with($subject, 'error:')) continue;

            $newChanges[] = [
                'hash' => $hash,
                'subject' => $subject,
                'author' => $author,
                'date' => $date
            ];
        }

        // Fallback if no git commits could be parsed
        if (empty($newChanges)) {
            $newChanges[] = [
                'hash' => 'RELEASE',
                'subject' => "System maintenance, stability updates, and improvements for {$formattedVersion}",
                'author' => 'System',
                'date' => now()->toDateString()
            ];
        }

        $this->info("Found " . count($newChanges) . " changes for version {$formattedVersion}");

        if ($this->option('dry-run')) {
            foreach ($newChanges as $change) {
                $this->line("- [{$change['hash']}] {$change['subject']} ({$change['author']})");
            }
            return 0;
        }

        // Update CHANGELOG.md
        $this->updateChangelogFile($formattedVersion, $newChanges);

        // Update database
        $this->updateDatabase($formattedVersion, $newChanges);

        $this->info("Successfully updated CHANGELOG.md and database for {$formattedVersion}.");
        return 0;
    }

    protected function updateChangelogFile($version, $changes)
    {
        $changelogPath = base_path('CHANGELOG.md');
        $date = now()->toDateString();
        
        $newContent = "## [{$version}] - {$date}\n";
        foreach ($changes as $change) {
            $lines = explode("\n", $change['subject']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $line = ltrim($line, '- ');
                $newContent .= "- {$line} ([{$change['hash']}])\n";
            }
        }
        $newContent .= "\n";

        if (file_exists($changelogPath)) {
            $existingContent = file_get_contents($changelogPath);
            if (str_contains($existingContent, "## [{$version}]")) {
                // Already has this section, replace it or keep
                return;
            }
            if (str_contains($existingContent, '# Changelog')) {
                $content = str_replace('# Changelog', "# Changelog\n\n" . $newContent, $existingContent);
            } else {
                $content = $newContent . $existingContent;
            }
        } else {
            $content = "# Changelog\n\n" . $newContent;
        }

        file_put_contents($changelogPath, $content);
    }

    protected function updateDatabase($version, $changes)
    {
        $cleanVersion = ltrim($version, 'v');
        $formattedVersion = "v{$cleanVersion}";

        // Check if entry already exists
        $entry = Changelog::where('version', $version)
            ->orWhere('version', $formattedVersion)
            ->orWhere('version', $cleanVersion)
            ->first();
        
        $descriptionLines = [];
        foreach (array_slice($changes, 0, 8) as $change) {
            $subject = trim(ltrim($change['subject'], '- '));
            if (!empty($subject)) {
                $descriptionLines[] = "• {$subject}";
            }
        }
        $description = implode("\n", $descriptionLines);

        if ($entry) {
            $entry->update([
                'version' => $formattedVersion,
                'title' => "Release {$formattedVersion}",
                'description' => $description,
                'release_date' => now()->toDateString(),
            ]);
        } else {
            Changelog::create([
                'version' => $formattedVersion,
                'title' => "Release {$formattedVersion}",
                'description' => $description,
                'type' => 'feature',
                'release_date' => now()->toDateString(),
            ]);
        }
    }
}
