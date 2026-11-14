<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewsCompileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `php artisan view:cache` bisa sukses walau PHP hasil kompilasi Blade tidak valid
     * (mis. `route(\'x\')`). Test ini mem-parse PHP hasil kompilasi setiap view.
     */
    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $errors = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $php = Blade::compileString($file->getContents());
            try {
                token_get_all($php, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $errors[] = $file->getRelativePathname() . ': ' . $e->getMessage() . ' (baris kompilasi ' . $e->getLine() . ')';
            }
        }

        $this->assertSame([], $errors, "View dengan PHP tidak valid:\n" . implode("\n", $errors));
    }

    public function test_admin_ticket_detail_renders(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-TKT-SHOW', 'username' => 'cust_tkt_show', 'name' => 'Pelanggan Tiket',
            'is_active' => true, 'status' => Customer::STATUS_ACTIVE,
        ]);
        $ticket = Ticket::create([
            'customer_id' => $customer->id, 'type' => 'gangguan', 'status' => 'open', 'priority' => 'medium',
            'subject' => 'Internet mati', 'description' => 'Lampu LOS merah',
        ]);
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        $this->actingAs($admin)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Internet mati')
            ->assertSee(route('tickets.edit', $ticket), false);
    }
}
