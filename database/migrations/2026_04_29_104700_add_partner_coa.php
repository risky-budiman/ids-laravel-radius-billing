<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ChartOfAccount;

return new class extends Migration
{
    public function up(): void
    {
        // Add COA for Partner Commissions
        $parentLiability = ChartOfAccount::where('code', '2000')->first();
        $parentExpense = ChartOfAccount::where('code', '5000')->first();

        if ($parentLiability) {
            ChartOfAccount::updateOrCreate(
                ['code' => '2104'],
                [
                    'name' => 'Hutang Komisi Mitra',
                    'type' => 'liability',
                    'parent_id' => $parentLiability->id
                ]
            );
        }

        if ($parentExpense) {
            ChartOfAccount::updateOrCreate(
                ['code' => '5110'],
                [
                    'name' => 'Beban Komisi Mitra',
                    'type' => 'expense',
                    'parent_id' => $parentExpense->id
                ]
            );
        }
    }

    public function down(): void
    {
        ChartOfAccount::whereIn('code', ['2104', '5110'])->delete();
    }
};
