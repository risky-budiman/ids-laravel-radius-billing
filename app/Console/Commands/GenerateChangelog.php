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
        $this->info('Generating changelog from git logs...');

        // Get last version from DB to know where to start git log
        $latestEntry = Changelog::latest('id')->first();
        $since = $latestEntry ? $latestEntry->created_at->toDateTimeString() : '';

        // Execute git log command
        $gitCommand = "git log " . ($since ? "--since=\"{$since}\" " : "") . "--pretty=format:\"%h|%s|%an|%ad\" --date=short";
        $output = shell_exec($gitCommand);

        $lines = $output ? explode("\n", trim($output)) : [];
        $newChanges = [];

        // Add manual message if provided (e.g. from commit-msg hook)
        if ($this->option('message')) {
            $newChanges[] = [
                'hash' => 'HEAD',
                'subject' => $this->option('message'),
                'author' => 'System',
                'date' => now()->toDateString()
            ];
        }

        if (!$output && empty($newChanges)) {
            $this->warn('No new changes found since last release.');
            return;
        }

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 2) continue;

            $hash = $parts[0];
            $subject = $parts[1];
            $author = $parts[2] ?? 'Unknown';
            $date = $parts[3] ?? now()->toDateString();

            // Skip merge commits if needed
            if (str_starts_with($subject, 'Merge branch')) continue;

            $newChanges[] = [
                'hash' => $hash,
                'subject' => $subject,
                'author' => $author,
                'date' => $date
            ];
        }

        if (empty($newChanges)) {
            $this->warn('No relevant changes found.');
            return;
        }

        $version = app_version();
        $this->info("Found " . count($newChanges) . " changes for version {$version}");

        if ($this->option('dry-run')) {
            foreach ($newChanges as $change) {
                $this->line("- [{$change['hash']}] {$change['subject']} ({$change['author']})");
            }
            return;
        }

        // Update CHANGELOG.md
        $this->updateChangelogFile($version, $newChanges);

        // Update database (optional: could be more detailed)
        $this->updateDatabase($version, $newChanges);

        $this->info('Successfully updated CHANGELOG.md and database.');
    }

    protected function updateChangelogFile($version, $changes)
    {
        $changelogPath = base_path('CHANGELOG.md');
        $date = now()->toDateString();
        
        $newContent = "## [{$version}] - {$date}\n";
        foreach ($changes as $change) {
            $newContent .= "- {$change['subject']} ([{$change['hash']}])\n";
        }
        $newContent .= "\n";

        if (file_exists($changelogPath)) {
            $existingContent = file_get_contents($changelogPath);
            // Insert at the top after the title
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
        // Check if entry already exists
        $entry = Changelog::where('version', $version)->first();
        
        $description = "";
        foreach ($changes as $change) {
            $description .= "- {$change['subject']}\n";
        }

        if ($entry) {
            $entry->update([
                'description' => $description,
                'release_date' => now()->toDateString(),
            ]);
        } else {
            Changelog::create([
                'version' => $version,
                'title' => "Release {$version}",
                'description' => $description,
                'type' => 'feature',
                'release_date' => now()->toDateString(),
            ]);
        }
    }
}
