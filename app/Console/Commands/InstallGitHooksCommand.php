<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Hook git untuk versi & changelog otomatis:
 *  - pre-commit  : naikkan VERSION (patch) dan ikutkan ke commit.
 *  - post-commit : susun ulang CHANGELOG.md (kini pesan commit sudah ada) lalu amend commit yang sama,
 *                  sehingga versi baru langsung tercatat beserta isinya.
 * Dilewati saat rebase / cherry-pick / merge agar riwayat tidak berubah di tengah proses.
 */
class InstallGitHooksCommand extends Command
{
    protected $signature = 'app:install-hooks';

    protected $description = 'Pasang hook git untuk kenaikan versi & changelog otomatis';

    private const SKIP_DURING_SEQUENCE = <<<'SH'
GIT_DIR=$(git rev-parse --git-dir)
for f in rebase-merge rebase-apply CHERRY_PICK_HEAD MERGE_HEAD REVERT_HEAD; do
    [ -e "$GIT_DIR/$f" ] && exit 0
done
SH;

    public function handle(): int
    {
        $hookDir = base_path('.git/hooks');
        if (! is_dir($hookDir)) {
            $this->warn('Bukan repositori git atau folder .git/hooks tidak ditemukan.');

            return self::FAILURE;
        }

        $preCommit = "#!/bin/sh\n# Dipasang oleh: php artisan app:install-hooks\n[ -n \"\$CHANGELOG_AMEND\" ] && exit 0\n"
            . self::SKIP_DURING_SEQUENCE . "\n\n"
            . "if command -v php >/dev/null 2>&1; then\n"
            . "    php artisan app:bump-version patch --no-interaction\n"
            . "    git add VERSION 2>/dev/null || true\n"
            . "fi\n\nexit 0\n";

        $postCommit = "#!/bin/sh\n# Dipasang oleh: php artisan app:install-hooks\n[ -n \"\$CHANGELOG_AMEND\" ] && exit 0\n"
            . self::SKIP_DURING_SEQUENCE . "\n\n"
            . "if command -v php >/dev/null 2>&1; then\n"
            . "    php artisan app:generate-changelog --write --no-interaction >/dev/null 2>&1 || exit 0\n"
            . "    if ! git diff --quiet -- CHANGELOG.md; then\n"
            . "        git add CHANGELOG.md\n"
            . "        CHANGELOG_AMEND=1 git commit --amend --no-edit --no-verify -q\n"
            . "        echo \"[Changelog] v\$(cat VERSION) dicatat di CHANGELOG.md\"\n"
            . "    fi\n"
            . "fi\n\nexit 0\n";

        foreach (['pre-commit' => $preCommit, 'post-commit' => $postCommit] as $name => $script) {
            file_put_contents("{$hookDir}/{$name}", $script);
            @chmod("{$hookDir}/{$name}", 0775);
        }

        $this->info('Hook git terpasang: pre-commit (naikkan VERSION) & post-commit (CHANGELOG.md).');

        return self::SUCCESS;
    }
}
