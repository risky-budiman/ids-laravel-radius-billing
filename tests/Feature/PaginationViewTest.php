<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/** Tampilan pagination aplikasi (resources/views/vendor/pagination) menggantikan bawaan Laravel. */
class PaginationViewTest extends TestCase
{
    private function render(int $total, int $page, array $data = []): string
    {
        $count = max(0, min(50, $total - ($page - 1) * 50));
        $p = new LengthAwarePaginator($count ? range(1, $count) : [], $total, 50, $page, ['path' => '/admin/x']);

        return (string) $p->links(null, $data);
    }

    public function test_indonesian_summary_and_compact_page_numbers(): void
    {
        $html = $this->render(573, 2);
        $this->assertStringContainsString('Navigasi halaman', $html);
        $this->assertStringContainsString('51–100', $html);
        $this->assertStringContainsString('573', $html);
        $this->assertStringContainsString('Sebelumnya', $html);
        $this->assertStringContainsString('Berikutnya', $html);
        $this->assertStringContainsString('aria-current="page">2<', $html);
        $this->assertStringContainsString('page=12', $html);       // halaman terakhir
        $this->assertStringNotContainsString('page=8"', $html);    // halaman tengah diringkas dengan …
        $this->assertStringNotContainsString('Showing', $html);
    }

    public function test_single_page_label_and_out_of_range(): void
    {
        $this->assertStringContainsString('12</span> pelanggan', $this->render(12, 1, ['label' => 'pelanggan']));
        $html = $this->render(12, 4);
        $this->assertStringContainsString('Halaman 4 kosong', $html);
        $this->assertStringNotContainsString('0–0', $html);
        $this->assertSame('', trim($this->render(0, 1)));
    }
}
