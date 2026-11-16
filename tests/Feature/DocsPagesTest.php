<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Semua halaman dokumentasi (docs/*.md) bisa dibuka dan memakai nama menu yang berlaku. */
class DocsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_docs_page_renders(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrator', 'is_active' => true]));
        $files = File::files(base_path('docs'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $slug = $file->getFilenameWithoutExtension();
            $this->get(route('docs.index', $slug))->assertOk();
            // Nama menu lama berbahasa Inggris yang sudah tidak ada di aplikasi
            $text = File::get($file->getPathname());
            foreach (['`Billing`', 'Support > Tickets', 'Accounting > Reports', 'Settings > Company Profile', 'Inventory > Items', 'Mark as Paid'] as $old) {
                $this->assertStringNotContainsString($old, $text, "{$slug}.md masih memakai '{$old}'");
            }
        }
        $this->get(route('docs.index', 'deploy'))->assertSee('Checklist Deploy');
    }
}
