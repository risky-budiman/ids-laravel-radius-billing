<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerImportController;
use App\Jobs\RunCustomerImport;
use App\Models\Customer;
use App\Models\ImportRun;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Impor pelanggan file besar di latar belakang (antrean "imports") dengan kemajuan & hasil tersimpan. */
class CustomerImportBackgroundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        CustomerImportController::$backgroundRows = 2;   // file 3 baris dianggap "besar"
        $this->admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        Package::create(['name' => 'Paket 10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        CustomerImportController::$backgroundRows = 300;
        parent::tearDown();
    }

    private function file(int $rows): UploadedFile
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('Format Import');
        $sheet->fromArray([['nama_pelanggan', 'username', 'nama_paket', 'no_telepon']], null, 'A1');
        for ($i = 1; $i <= $rows; $i++) {
            $sheet->fromArray([["Pelanggan Besar {$i}", "besar{$i}@net", 'Paket 10M', "08120000{$i}"]], null, 'A' . ($i + 1));
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);

        return new UploadedFile($path, 'besar.xlsx', null, null, true);
    }

    public function test_large_preview_then_import_run_in_background(): void
    {
        Queue::fake();
        $this->actingAs($this->admin);

        // Pratinjau: diantrekan di "imports", tidak ada yang disimpan
        $res = $this->post(route('customers.import.store'), ['file' => $this->file(3), 'duplicate_action' => 'skip', 'mode' => 'preview']);
        $run = ImportRun::sole();
        $res->assertRedirect(route('customers.import.index', ['run' => $run->id]));
        Queue::assertPushedOn('imports', RunCustomerImport::class);
        $this->assertSame(['preview', 'queued', 3], [$run->mode, $run->status, $run->total_rows]);

        $this->get(route('customers.import.index', ['run' => $run->id]))->assertOk()->assertSee('berjalan di latar belakang');
        $this->getJson(route('customers.import.status', $run))->assertOk()->assertJson(['status' => 'queued', 'started' => false]);

        (new RunCustomerImport($run->id))->handle(app(\App\Services\CustomerImportService::class));
        $run->refresh();
        $this->assertSame(['done', 3, 0], [$run->status, $run->result['success'], Customer::count()]);

        // Hasil pratinjau tampil + tombol "Impor sekarang" memakai file yang sama
        $this->assertSame(session('import_pending')['token'], $run->id, 'id proses = token pratinjau');
        $this->get(route('customers.import.index', ['run' => $run->id]))->assertOk()
            ->assertSee('Pratinjau Impor')->assertSee('name="token" value="' . $run->id . '"', false);

        $this->post(route('customers.import.store'), ['token' => $run->id, 'duplicate_action' => 'skip', 'mode' => 'import'])->assertRedirect();
        $import = ImportRun::where('mode', 'import')->sole();
        (new RunCustomerImport($import->id))->handle(app(\App\Services\CustomerImportService::class));
        $import->refresh();
        $this->assertSame(['done', 3], [$import->status, Customer::count()]);
        $this->assertSame(100, $import->percent());
        $this->assertTrue(\App\Models\ActivityLog::where('action', 'customers_imported')->exists());
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('local')->exists($import->path), 'file impor dihapus setelah selesai');
    }

    public function test_small_files_still_import_immediately(): void
    {
        Queue::fake();
        $this->actingAs($this->admin)->post(route('customers.import.store'), ['file' => $this->file(2), 'duplicate_action' => 'skip'])
            ->assertRedirect(route('customers.import.index'));
        $this->assertSame(0, ImportRun::count());
        $this->assertSame(2, Customer::count());
    }

    public function test_failed_run_is_reported_and_runs_are_private(): void
    {
        $run = ImportRun::create(['user_id' => $this->admin->id, 'mode' => 'import', 'file_name' => 'hilang.xlsx',
            'path' => 'imports/tidak-ada.xlsx', 'duplicate_action' => 'skip', 'total_rows' => 500]);
        (new RunCustomerImport($run->id))->handle(app(\App\Services\CustomerImportService::class));
        $this->assertSame('failed', $run->fresh()->status);
        $this->actingAs($this->admin)->get(route('customers.import.index', ['run' => $run->id]))->assertOk()->assertSee('gagal');

        $other = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($other)->getJson(route('customers.import.status', $run))->assertNotFound();
        $this->actingAs($other)->get(route('customers.import.index', ['run' => $run->id]))->assertNotFound();
    }
}
