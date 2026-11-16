<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadUserGroup;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Impor/ekspor pelanggan: hanya admin, mode perbarui tidak menghapus data, status & paket lewat alur yang benar.
 */
class CustomerImportExportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['nama_pelanggan', 'username', 'password', 'nama_paket', 'no_telepon', 'tipe_tagihan', 'metode_tagihan',
        'tgl_jatuh_tempo', 'kenakan_ppn', 'tipe_diskon', 'nilai_diskon', 'status', 'tgl_aktivasi', 'id_pelanggan', 'email', 'nik_ktp',
        'alamat', 'serial_number_onu', 'latitude', 'longitude'];

    private User $admin;
    private Package $p10;
    private Package $p20;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Process::fake();
        $this->admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $this->p10 = Package::create(['name' => 'Paket 10M', 'type' => 'pppoe', 'price' => 150000, 'upload_speed' => '10M', 'download_speed' => '10M', 'is_active' => true]);
        $this->p20 = Package::create(['name' => 'Paket 20M', 'type' => 'pppoe', 'price' => 250000, 'upload_speed' => '20M', 'download_speed' => '20M', 'is_active' => true]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function file(array $rows): UploadedFile
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setTitle('Format Import');
        $sheet->fromArray([self::HEADERS], null, 'A2');
        $r = 3;
        foreach ($rows as $row) {
            foreach (self::HEADERS as $i => $h) {
                $v = $row[$h] ?? '';
                // int/float = sel berformat angka (seperti kolom Excel yang tidak berformat teks)
                if (is_int($v) || is_float($v)) {
                    $sheet->setCellValueExplicit([$i + 1, $r], $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit([$i + 1, $r], (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            $r++;
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($sheet->getParent()))->save($path);

        return new UploadedFile($path, 'impor.xlsx', null, null, true);
    }

    private function existing(): Customer
    {
        $c = Customer::create(['customer_code' => '001001001500', 'username' => 'lama@net', 'password' => 'rahasia1', 'name' => 'Pelanggan Lama',
            'package_id' => $this->p10->id, 'phone' => '0811', 'email' => 'lama@mail.id', 'ktp' => '3201000000000001', 'address' => 'Jl. Lama 1',
            'onu_sn' => 'ZTEGC0000001', 'latitude' => -6.2, 'longitude' => 106.8, 'discount_type' => 'fixed', 'discount_value' => 20000, 'use_tax' => true]);
        $c->update(['status' => Customer::STATUS_ACTIVE, 'is_active' => true]);
        RadCheck::create(['username' => 'lama@net', 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'rahasia1']);
        RadUserGroup::insert(['username' => 'lama@net', 'groupname' => 'Paket 10M', 'priority' => 1]);

        return $c->fresh();
    }

    public function test_only_admins_can_import_or_export(): void
    {
        foreach (['teknisi', 'sales', 'kasir', 'cs'] as $role) {
            $u = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($u)->get(route('customers.export'))->assertForbidden();
            $this->actingAs($u)->get(route('customers.import.index'))->assertForbidden();
            $this->actingAs($u)->post(route('customers.import.store'), ['file' => $this->file([]), 'duplicate_action' => 'update'])->assertForbidden();
        }
    }

    public function test_index_buttons_follow_role_and_render_no_raw_directives(): void
    {
        $this->actingAs($this->admin)->get(route('customers.index'))->assertOk()
            ->assertSee(route('customers.import.index'))->assertSee('with_passwords=1', false)
            ->assertDontSee('\if(', false)->assertDontSee('\endif', false)->assertDontSee('\unless', false);

        $teknisi = User::factory()->create(['role' => 'teknisi', 'is_active' => true]);
        $this->actingAs($teknisi)->get(route('customers.index'))->assertOk()
            ->assertDontSee(route('customers.import.index'))->assertDontSee(route('customers.export'))
            ->assertSee(route('customers.create'));

        $cs = User::factory()->create(['role' => 'cs', 'is_active' => true]);
        $this->actingAs($cs)->get(route('customers.index'))->assertOk()
            ->assertDontSee(route('customers.create'))->assertDontSee(route('customers.export'));
    }

    public function test_update_mode_keeps_blank_columns_and_password_and_ignores_status(): void
    {
        $c = $this->existing();

        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'update', 'file' => $this->file([[
            'nama_pelanggan' => 'Pelanggan Lama Baru', 'username' => 'lama@net', 'nama_paket' => 'Paket 20M', 'no_telepon' => '0812999',
            'status' => 'suspended',   // diabaikan untuk pelanggan lama
        ]])])->assertRedirect();

        $c->refresh();
        $this->assertSame('Pelanggan Lama Baru', $c->name);
        $this->assertSame('0812999', $c->phone);
        // Kolom kosong tidak menghapus data (dulu menjadi kosong)
        $this->assertSame('lama@mail.id', $c->email);
        $this->assertSame('3201000000000001', $c->ktp);
        $this->assertSame('Jl. Lama 1', $c->address);
        $this->assertSame('ZTEGC0000001', $c->onu_sn);
        $this->assertEqualsWithDelta(-6.2, (float) $c->latitude, 0.0001);
        $this->assertSame('fixed', $c->discount_type);
        $this->assertTrue((bool) $c->use_tax);
        // Password kosong = tetap (dulu diganti password acak -> pelanggan terputus)
        $this->assertSame('rahasia1', RadCheck::where('username', 'lama@net')->value('value'));
        // Status lama tetap; paket berganti lewat alur resmi (grup RADIUS ikut)
        $this->assertSame(Customer::STATUS_ACTIVE, $c->status);
        $this->assertSame($this->p20->id, $c->package_id);
        $this->assertSame('Paket 20M', RadUserGroup::where('username', 'lama@net')->value('groupname'));
        $notes = session('import_result')['notes'] ?? [];
        $this->assertStringContainsString('diabaikan', $notes[0]['reason'] ?? '');
    }

    public function test_new_suspended_customer_stays_isolated_without_activation_ticket(): void
    {
        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'file' => $this->file([[
            'nama_pelanggan' => 'Pindahan Terisolir', 'username' => 'pindah@net', 'password' => 'abc12345', 'nama_paket' => 'Paket 10M',
            'no_telepon' => '0813', 'status' => 'suspended', 'id_pelanggan' => '001001001777',
        ]])])->assertRedirect();

        $c = Customer::where('username', 'pindah@net')->sole();
        $this->assertSame(Customer::STATUS_SUSPENDED, $c->status, 'dulu menjadi menunggu aktivasi');
        $this->assertFalse(Ticket::where('customer_id', $c->id)->where('type', 'aktivasi')->exists());
    }

    public function test_indonesian_numbers_and_numeric_cells_are_read_correctly(): void
    {
        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'file' => $this->file([[
            'nama_pelanggan' => 'Angka Indo', 'nama_paket' => 'Paket 20M', 'no_telepon' => 81234567890,   // sel angka: 0 di depan hilang
            'id_pelanggan' => 1001001777,                                                                  // sel angka: 00 di depan hilang
            'nik_ktp' => 3201000000000001.0,                                                               // sel angka: dibulatkan Excel
            'tipe_diskon' => 'nominal', 'nilai_diskon' => '20.000', 'latitude' => '-6,2088', 'longitude' => '106,8456',
        ]])])->assertRedirect();

        $c = Customer::where('name', 'Angka Indo')->sole();
        $this->assertSame('081234567890', $c->phone);
        $this->assertSame('001001001777', $c->customer_code);
        $this->assertEquals(20000, (float) $c->discount_value, 'dulu terbaca 20');
        $this->assertEqualsWithDelta(-6.2088, (float) $c->latitude, 0.00001, 'dulu diabaikan');
        $this->assertEqualsWithDelta(106.8456, (float) $c->longitude, 0.00001);
        $this->assertNull($c->ktp, 'NIK dari sel angka tidak bisa dipercaya');
        $notes = collect(session('import_result')['notes'])->pluck('reason')->implode(' ');
        $this->assertStringContainsString('NIK diabaikan', $notes);
    }

    public function test_invalid_values_are_rejected_with_reasons_instead_of_silent_defaults(): void
    {
        $base = ['nama_paket' => 'Paket 10M', 'no_telepon' => '0811'];
        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'file' => $this->file([
            $base + ['nama_pelanggan' => 'Status Salah', 'status' => 'aktif'],
            $base + ['nama_pelanggan' => 'Tanggal Salah', 'tgl_aktivasi' => 'kemarin'],
            $base + ['nama_pelanggan' => 'Persen Salah', 'tipe_diskon' => 'persen', 'nilai_diskon' => '150'],
            $base + ['nama_pelanggan' => 'Tagihan Salah', 'tipe_tagihan' => 'bulanan'],
            ['nama_pelanggan' => 'Tanpa HP', 'nama_paket' => 'Paket 10M'],
            $base + ['nama_pelanggan' => 'Koordinat Salah', 'latitude' => '206.5'],
        ])])->assertRedirect();

        $res = session('import_result');
        $this->assertSame(0, $res['success']);
        $this->assertSame(6, $res['skipped']);
        $reasons = collect($res['errors'])->pluck('reason')->implode(' | ');
        foreach (["status 'aktif'", "tgl_aktivasi 'kemarin'", 'lebih dari 100', "tipe_tagihan 'bulanan'", 'no_telepon wajib', "latitude '206.5'"] as $needle) {
            $this->assertStringContainsString($needle, $reasons);
        }
        $this->assertSame(0, Customer::count());
    }

    public function test_customer_codes_never_collide(): void
    {
        $this->existing();   // memakai 001001001500
        $rows = [['nama_pelanggan' => 'Bentrok', 'nama_paket' => 'Paket 10M', 'no_telepon' => '0811', 'id_pelanggan' => '001001001500']];
        foreach (range(1, 3) as $i) {
            $rows[] = ['nama_pelanggan' => "Otomatis {$i}", 'nama_paket' => 'Paket 10M', 'no_telepon' => '0811'];
        }
        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'file' => $this->file($rows)])->assertRedirect();

        $res = session('import_result');
        $this->assertStringContainsString('sudah dipakai', $res['errors'][0]['reason'], 'dulu gagal dengan pesan error database');
        $codes = Customer::pluck('customer_code');
        $this->assertCount(4, $codes);
        $this->assertSame($codes->count(), $codes->unique()->count());
    }

    public function test_preview_saves_nothing_and_confirm_imports_the_same_file(): void
    {
        RadCheck::create(['username' => 'baru@net', 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'sisa-lama']);

        $this->actingAs($this->admin)->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'mode' => 'preview', 'file' => $this->file([
            ['nama_pelanggan' => 'Pelanggan Baru', 'username' => 'baru@net', 'password' => 'baru1234', 'nama_paket' => 'Paket 10M', 'no_telepon' => '0811'],
            ['nama_pelanggan' => 'Rusak', 'nama_paket' => 'Paket Tidak Ada', 'no_telepon' => '0811'],
        ])])->assertRedirect();

        $res = session('import_result');
        $this->assertTrue($res['dry_run']);
        $this->assertSame(1, $res['success']);
        $this->assertSame('dibuat', $res['preview'][0]['action']);
        $this->assertSame(0, Customer::count(), 'pratinjau tidak boleh menyimpan');

        $this->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'mode' => 'import', 'token' => $res['token']])->assertRedirect();
        $this->assertSame(1, Customer::where('username', 'baru@net')->count());
        // Sisa baris RADIUS lama tidak membuat password ganda
        $this->assertSame(1, RadCheck::where('username', 'baru@net')->count());
        $this->assertSame('baru1234', RadCheck::where('username', 'baru@net')->value('value'));

        // Token sekali pakai
        $this->post(route('customers.import.store'), ['duplicate_action' => 'skip', 'mode' => 'import', 'token' => $res['token']])
            ->assertSessionHas('error');
    }

    public function test_export_leaves_passwords_out_unless_requested(): void
    {
        $this->existing();
        $this->actingAs($this->admin);

        $read = function ($response) {
            $path = tempnam(sys_get_temp_dir(), 'exp') . '.xlsx';
            file_put_contents($path, $response->streamedContent());

            return IOFactory::load($path)->getSheet(0);
        };

        $sheet = $read($this->get(route('customers.export'))->assertOk());
        $this->assertSame('lama@net', $sheet->getCell('B3')->getValue());
        $this->assertSame('', (string) $sheet->getCell('C3')->getValue());

        $sheet = $read($this->get(route('customers.export', ['with_passwords' => 1]))->assertOk());
        $this->assertSame('rahasia1', $sheet->getCell('C3')->getValue());
        $this->assertTrue(ActivityLog::where('action', 'customers_exported')->where('description', 'like', '%TERMASUK password%')->exists());
    }
}
