<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\ChartOfAccount;

return new class extends Migration
{
    public function up(): void
    {
        $parentLiability = ChartOfAccount::where('code', '2000')->first();
        $parentExpense = ChartOfAccount::where('code', '5000')->first();

        if ($parentLiability) {
            ChartOfAccount::updateOrCreate(
                ['code' => '2105'],
                [
                    'name' => 'Hutang Insentif Sales',
                    'type' => 'liability',
                    'parent_id' => $parentLiability->id
                ]
            );
        }

        if ($parentExpense) {
            ChartOfAccount::updateOrCreate(
                ['code' => '5111'],
                [
                    'name' => 'Beban Insentif Sales',
                    'type' => 'expense',
                    'parent_id' => $parentExpense->id
                ]
            );
        }
    }

    public function down(): void
    {
        ChartOfAccount::whereIn('code', ['2105', '5111'])->delete();
    }
};
