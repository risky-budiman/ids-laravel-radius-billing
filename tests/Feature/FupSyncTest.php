<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Radius\RadReply;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * FUP (app:sync-usage) sebelumnya selalu gagal: model RadReply menulis ke tabel "rad_replies"
 * yang tidak ada. Selain itu FUP hanya mengubah baris radreply yang sudah ada.
 */
class FupSyncTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();

        Setting::updateOrCreate(['key' => 'enable_fup_module'], ['value' => '1']);
        Cache::forget('app_settings');

        Nas::forceCreate(['nasname' => '10.0.0.1', 'shortname' => 'nas-a', 'secret' => 'sec-a']);

        $package = Package::create([
            'name' => 'Broadband 10M', 'type' => 'pppoe', 'price' => 150000,
            'upload_speed' => '10M', 'download_speed' => '10M',
            'enable_fup' => true, 'fup_limit_gb' => 1, 'fup_speed_limit' => '1M/1M',
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-FUP', 'username' => 'cust_fup', 'name' => 'Pelanggan FUP',
            'package_id' => $package->id, 'is_active' => true, 'status' => Customer::STATUS_ACTIVE,
        ]);
        Customer::whereKey($this->customer->id)->update(['is_active' => true, 'status' => Customer::STATUS_ACTIVE]);
    }

    private function usage(float $gb, ?string $stop = null): void
    {
        RadAcct::forceCreate([
            'acctsessionid' => 'sess-fup', 'acctuniqueid' => md5('sess-fup'), 'username' => 'cust_fup',
            'nasipaddress' => '10.0.0.1', 'acctstarttime' => now()->startOfMonth()->addHour(), 'acctstoptime' => $stop,
            'acctinputoctets' => (int) ($gb * 1073741824), 'acctoutputoctets' => 0,
        ]);
    }

    public function test_customer_over_quota_gets_fup_speed_and_is_disconnected(): void
    {
        $this->usage(2.0);   // 2 GB > limit 1 GB, sesi masih online

        $this->artisan('app:sync-usage')->assertSuccessful();

        $reply = RadReply::where('username', 'cust_fup')->where('attribute', 'Mikrotik-Rate-Limit')->first();
        $this->assertNotNull($reply, 'Batasan FUP tidak dibuat di radreply');
        $this->assertSame('1M/1M', $reply->value);
        $this->assertEquals(2.0, (float) $this->customer->fresh()->current_month_usage_gb);
        Process::assertRan(fn (PendingProcess $p) => is_array($p->command) && in_array('10.0.0.1:3799', $p->command, true));
    }

    public function test_customer_under_quota_is_restored_to_normal_speed(): void
    {
        $this->usage(0.2);
        RadReply::create(['username' => 'cust_fup', 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => '1M/1M']);

        $this->artisan('app:sync-usage')->assertSuccessful();

        $this->assertSame('10M/10M', RadReply::where('username', 'cust_fup')->value('value'));
    }
}
