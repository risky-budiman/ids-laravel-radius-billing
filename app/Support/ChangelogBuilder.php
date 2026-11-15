<?php

namespace App\Support;

use App\Models\Changelog;
use Symfony\Component\Process\Process;

/**
 * Menyusun changelog per versi dari riwayat git dan menyinkronkannya ke tabel `changelogs`.
 *
 * Versi ditentukan oleh commit yang mengubah file VERSION (hook pre-commit menaikkannya di setiap
 * commit). Sebuah versi berisi semua commit sejak versi sebelumnya sampai commit yang menaikkan
 * versinya — biasanya tepat satu commit.
 */
class ChangelogBuilder
{
    public const SECTIONS = [
        'security' => 'Keamanan',
        'feature' => 'Fitur baru',
        'fix' => 'Perbaikan',
        'improvement' => 'Peningkatan',
    ];

    private const HEADER = "# Changelog\n\nRiwayat perubahan per versi. Disusun otomatis dari riwayat commit oleh `php artisan app:generate-changelog --write`.\n";

    /**
     * @return list<array{version: string, date: string, items: array<string, list<string>>}>|null  terbaru di atas
     */
    public function fromGit(string $repo): ?array
    {
        $commits = $this->git($repo, ['log', '--reverse', '--first-parent', '--date=short', '--format=%h%x1f%ad%x1f%s']);
        if ($commits === null || trim($commits) === '') {
            return null;
        }

        // Commit yang mengubah VERSION -> versi barunya
        $versionAt = [];
        $patch = (string) $this->git($repo, ['log', '--first-parent', '--format=@@%h', '-p', '--no-color', '--unified=0', '--', 'VERSION']);
        $current = null;
        foreach (explode("\n", $patch) as $line) {
            if (str_starts_with($line, '@@') && ! str_starts_with($line, '@@ ')) {
                $current = substr($line, 2);
            } elseif ($current && preg_match('/^\+(?!\+\+)\s*v?(\d+\.\d+\.\d+)\s*$/', $line, $m)) {
                $versionAt[$current] = $m[1];
            }
        }

        $releases = [];
        $pending = [];
        foreach (explode("\n", trim($commits)) as $row) {
            [$hash, $date, $subject] = array_pad(explode("\x1f", $row, 3), 3, '');
            $pending[] = [$date, $subject];
            if (isset($versionAt[$hash])) {
                $this->closeRelease($releases, $versionAt[$hash], $pending);
                $pending = [];
            }
        }
        if ($pending) {
            // Commit setelah kenaikan versi terakhir (mis. dibuat tanpa hook): masuk ke versi berjalan
            $this->closeRelease($releases, ltrim(trim((string) @file_get_contents($repo . '/VERSION')) ?: '0.0.0', 'v'), $pending);
        }

        return array_values(array_reverse($releases));
    }

    /** @param  array<string, array>  $releases */
    private function closeRelease(array &$releases, string $version, array $commits): void
    {
        $key = 'v' . $version;
        $releases[$key] ??= ['version' => $key, 'date' => '', 'items' => []];
        foreach ($commits as [$date, $subject]) {
            $releases[$key]['date'] = max($releases[$key]['date'], $date);
            [$section, $text] = $this->classify($subject);
            if ($text !== '' && ! in_array($text, $releases[$key]['items'][$section] ?? [], true)) {
                $releases[$key]['items'][$section][] = $text;
            }
        }
    }

    /** @return array{0: string, 1: string}  [section, teks rapi] */
    public function classify(string $subject): array
    {
        $s = trim(preg_replace('/\s*#(major|minor|patch)\b/i', '', $subject));

        if (preg_match('/^(\w+)(?:\(([^)]+)\))?!?:\s*(.+)$/', $s, $m)) {
            $type = strtolower($m[1]);
            $scope = strtolower($m[2] ?? '');
            $text = $this->ucfirst($m[3]);
            if ($scope !== '') {
                $text = "**{$scope}:** {$text}";
            }
            $section = match (true) {
                $scope === 'security' || $type === 'security' => 'security',
                $type === 'feat' => 'feature',
                $type === 'fix' => 'fix',
                default => 'improvement',
            };

            return [$section, $text];
        }

        // Pesan lama tanpa format conventional commit
        $lower = strtolower($s);
        $section = match (true) {
            (bool) preg_match('/\b(bug ?fix|fix|perbaik|perbaikan|error|gagal)/', $lower) => 'fix',
            (bool) preg_match('/\b(fitur|feature|add|tambah|penambahan|membuat|baru)/', $lower) => 'feature',
            default => 'improvement',
        };

        return [$section, $this->ucfirst($s)];
    }

    private function ucfirst(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    public function toMarkdown(array $releases): string
    {
        $out = self::HEADER;
        foreach ($releases as $r) {
            $out .= "\n## [{$r['version']}] - {$r['date']}\n";
            foreach (self::SECTIONS as $key => $label) {
                if (empty($r['items'][$key])) {
                    continue;
                }
                $out .= "\n### {$label}\n";
                foreach ($r['items'][$key] as $item) {
                    $out .= "- {$item}\n";
                }
            }
        }

        return $out;
    }

    /**
     * Baca CHANGELOG.md (format baru maupun lama).
     *
     * @return list<array{version: string, date: string, items: array<string, list<string>>}>
     */
    public function parseMarkdown(string $markdown): array
    {
        $labels = array_flip(array_map('strtolower', self::SECTIONS));
        $releases = [];
        $i = -1;
        $section = 'improvement';
        foreach (preg_split('/\R/', $markdown) as $line) {
            if (preg_match('/^##\s*\[v?([\d.]+)\]\s*-\s*(\S+)/', $line, $m)) {
                $releases[++$i] = ['version' => 'v' . $m[1], 'date' => $m[2], 'items' => []];
                $section = 'improvement';
            } elseif ($i >= 0 && preg_match('/^###\s*(.+)$/', $line, $m)) {
                $section = $labels[strtolower(trim($m[1]))] ?? 'improvement';
            } elseif ($i >= 0 && preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
                $text = trim(preg_replace('/\s*\(\[[0-9a-f]{7,}\]\)\s*$/', '', $m[1]));   // buang hash format lama
                if ($text !== '') {
                    $releases[$i]['items'][$section][] = $text;
                }
            }
        }

        return $releases;
    }

    /** Upsert per versi. Entri manual (versi yang tidak ada di file) tidak disentuh. */
    public function syncDatabase(array $releases): int
    {
        foreach ($releases as $r) {
            $plain = ltrim($r['version'], 'v');
            $lines = [];
            foreach (self::SECTIONS as $key => $label) {
                foreach ($r['items'][$key] ?? [] as $item) {
                    $lines[] = "[{$label}] " . str_replace('**', '', $item);
                }
            }
            $type = match (true) {
                ! empty($r['items']['security']) => 'security',
                ! empty($r['items']['feature']) => 'feature',
                ! empty($r['items']['fix']) => 'fix',
                default => 'improvement',
            };
            $counts = [];
            foreach (self::SECTIONS as $key => $label) {
                if ($n = count($r['items'][$key] ?? [])) {
                    $counts[] = $n . ' ' . strtolower($label);
                }
            }

            $data = [
                'version' => $r['version'],
                'title' => $counts ? ucfirst(implode(', ', $counts)) : 'Pemeliharaan',
                'description' => implode("\n", $lines) ?: 'Pemeliharaan sistem.',
                'type' => $type,
                'release_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $r['date']) ? $r['date'] : now()->toDateString(),
            ];
            // Pakai entri berformat "v1.2.3" bila ada; duplikat format lama "1.2.3" dihapus dulu (kolom version unik)
            $matches = Changelog::whereIn('version', [$r['version'], $plain])->get()
                ->sortBy(fn ($c) => [$c->version === $r['version'] ? 0 : 1, $c->id]);
            $entry = $matches->first();
            if ($matches->count() > 1) {
                Changelog::whereIn('id', $matches->skip(1)->pluck('id'))->delete();
            }
            $entry ? $entry->update($data) : Changelog::create($data);
        }

        return count($releases);
    }

    private function git(string $repo, array $args): ?string
    {
        if (! is_dir($repo . '/.git')) {
            return null;
        }
        $p = new Process(array_merge(['git', '-c', 'safe.directory=*', '-c', 'core.quotepath=off', '-c', 'i18n.logOutputEncoding=UTF-8'], $args), $repo);
        $p->setTimeout(60);
        $p->run();

        return $p->isSuccessful() ? $p->getOutput() : null;
    }
}
