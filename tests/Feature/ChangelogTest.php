<?php

namespace Tests\Feature;

use App\Models\Changelog;
use App\Models\User;
use App\Support\ChangelogBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangelogTest extends TestCase
{
    use RefreshDatabase;

    public function test_classifies_conventional_and_legacy_subjects(): void
    {
        $b = new ChangelogBuilder;
        $this->assertSame(['feature', '**billing:** Add reminders'], $b->classify('feat(billing): add reminders'));
        $this->assertSame(['security', '**security:** Rate limit'], $b->classify('fix(security): rate limit'));
        $this->assertSame(['fix', 'Typo'], $b->classify('fix: typo'));
        $this->assertSame(['improvement', 'Simplify'], $b->classify('refactor: simplify'));
        $this->assertSame(['fix', 'Perbaikan sidebar'], $b->classify('Perbaikan sidebar #patch'));
        $this->assertSame(['feature', 'Penambahan fitur ticket'], $b->classify('Penambahan fitur ticket'));
    }

    public function test_markdown_round_trip_and_database_sync_without_duplicates(): void
    {
        $b = new ChangelogBuilder;
        $releases = [
            ['version' => 'v1.2.1', 'date' => '2026-11-15', 'items' => ['fix' => ['**ui:** Tombol dobel']]],
            ['version' => 'v1.2.0', 'date' => '2026-11-14', 'items' => ['feature' => ['Laporan baru'], 'security' => ['Batas login']]],
        ];
        $parsed = $b->parseMarkdown($b->toMarkdown($releases));
        $this->assertEquals($releases, $parsed);

        // Format lama: hash di akhir baris dibuang
        $old = $b->parseMarkdown("# Changelog\n\n## [v1.0.0] - 2026-04-28\n- Add fitur ([abc1234])\n");
        $this->assertSame(['improvement' => ['Add fitur']], $old[0]['items']);

        Changelog::create(['version' => '1.2.0', 'title' => 'Release 1.2.0', 'description' => 'x', 'type' => 'feature', 'release_date' => '2026-01-01']);
        Changelog::create(['version' => 'v1.2.0', 'title' => 'dup', 'description' => 'x', 'type' => 'feature', 'release_date' => '2026-01-01']);
        Changelog::create(['version' => 'v0.9.0', 'title' => 'Manual', 'description' => 'manual', 'type' => 'fix', 'release_date' => '2026-01-01']);

        $b->syncDatabase($parsed);
        $b->syncDatabase($parsed);   // idempoten

        $this->assertSame(3, Changelog::count());
        $v120 = Changelog::where('version', 'v1.2.0')->sole();
        $this->assertSame('security', $v120->type);
        $this->assertSame("[Keamanan] Batas login\n[Fitur baru] Laporan baru", $v120->description);
        $this->assertSame('1 keamanan, 1 fitur baru', $v120->title);
        $this->assertSame('ui: Tombol dobel', explode('] ', Changelog::where('version', 'v1.2.1')->sole()->description)[1]);
        $this->assertSame('manual', Changelog::where('version', 'v0.9.0')->sole()->description, 'entri manual tidak disentuh');
    }

    public function test_changelog_page_renders_sorted_by_version(): void
    {
        Changelog::create(['version' => 'v1.10.0', 'title' => 't', 'description' => '[Fitur baru] Sepuluh', 'type' => 'feature', 'release_date' => '2026-02-01']);
        Changelog::create(['version' => 'v1.9.0', 'title' => 't', 'description' => '[Perbaikan] Sembilan', 'type' => 'fix', 'release_date' => '2026-01-01']);
        Changelog::create(['version' => 'v' . ltrim(app_version(), 'v'), 'title' => 't', 'description' => 'Sekarang', 'type' => 'fix', 'release_date' => '2026-01-01']);

        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]))
            ->get(route('changelog.index'))->assertOk()
            ->assertSeeInOrder(['v1.10.0', 'Sepuluh', 'v1.9.0', 'Sembilan'])
            ->assertSee('Versi terpasang');
    }
}
