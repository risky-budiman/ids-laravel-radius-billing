<?php

namespace Database\Seeders;

use App\Models\Tax;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure necessary COAs exist for Tax
        $taxAccount = ChartOfAccount::where('code', '2103')->first();
        if (!$taxAccount) {
            $parent = ChartOfAccount::where('code', '2100')->first();
            $taxAccount = ChartOfAccount::create([
                'code' => '2103',
                'name' => 'Hutang Pajak (PPN)',
                'type' => 'liability',
                'parent_id' => $parent ? $parent->id : null
            ]);
        }

        Tax::updateOrCreate(
            ['code' => 'PPN-11'],
            [
                'name' => 'PPN 11%',
                'rate' => 11,
                'is_active' => true,
                'chart_of_account_id' => $taxAccount->id
            ]
        );
    }
}
