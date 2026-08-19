<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallGitHooksCommand extends Command
{
    protected $signature = 'app:install-hooks';
    protected $description = 'Install Git pre-commit hook for automatic version bumping';

    public function handle()
    {
        $hookDir = base_path('.git/hooks');
        if (!is_dir($hookDir)) {
            $this->warn('Not a git repository or .git/hooks directory not found.');
            return 1;
        }

        $preCommitFile = "{$hookDir}/pre-commit";
        $script = "#!/bin/sh\n\nif command -v php >/dev/null 2>&1; then\n    php artisan app:bump-version patch --no-interaction\n    git add VERSION CHANGELOG.md 2>/dev/null || true\n    NEW_VER=$(cat VERSION 2>/dev/null || echo 'updated')\n    echo \"🚀 [Auto-Versioning] Version automatically bumped to v\${NEW_VER}\"\nfi\n\nexit 0\n";

        file_put_contents($preCommitFile, $script);
        @chmod($preCommitFile, 0775);

        $this->info('✅ Git pre-commit hook installed successfully! Every commit will now auto-increment the version.');
        return 0;
    }
}
