<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FixedAsset;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalculateDepreciation extends Command
{
    protected $signature = 'app:calculate-depreciation';
    protected $description = 'Calculate and journal fixed asset depreciation for the current month';

    public function handle()
    {
        $this->info('Starting depreciation calculation...');
        $assets = FixedAsset::where('status', 'active')
            ->whereRaw('accumulated_depreciation < (purchase_price - salvage_value)')
            ->get();

        if ($assets->isEmpty()) {
            $this->info('No active assets found requiring depreciation.');
            return;
        }

        DB::beginTransaction();
        try {
            $totalDepreciated = 0;
            $journalDate = Carbon::now()->endOfMonth()->toDateString();
            
            foreach ($assets as $asset) {
                $depreciableAmount = $asset->purchase_price - $asset->salvage_value;
                $monthlyDepreciation = $depreciableAmount / $asset->useful_life_months;
                
                // Adjust if remaining is less than monthly
                $remainingToDepreciate = $depreciableAmount - $asset->accumulated_depreciation;
                if ($monthlyDepreciation > $remainingToDepreciate) {
                    $monthlyDepreciation = $remainingToDepreciate;
                }

                if ($monthlyDepreciation <= 0) continue;

                $journal = Journal::create([
                    'journal_number' => 'DEP-' . strtoupper(uniqid()),
                    'date' => $journalDate,
                    'reference' => 'Depresiasi Aset: ' . $asset->asset_code,
                    'description' => 'Beban penyusutan bulanan untuk ' . $asset->name,
                    'status' => 'posted',
                ]);

                // Debit Depreciation Expense
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $asset->depreciation_account_id,
                    'description' => 'Beban penyusutan: ' . $asset->name,
                    'debit' => $monthlyDepreciation,
                    'credit' => 0,
                ]);

                // Credit Accumulated Depreciation
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $asset->accumulated_account_id,
                    'description' => 'Akumulasi penyusutan: ' . $asset->name,
                    'debit' => 0,
                    'credit' => $monthlyDepreciation,
                ]);

                $asset->accumulated_depreciation += $monthlyDepreciation;
                $asset->save();

                $totalDepreciated += $monthlyDepreciation;
                $this->line("Depreciated {$asset->name} by " . number_format($monthlyDepreciation, 2));
            }

            DB::commit();
            $this->info("Depreciation calculation completed. Total: " . number_format($totalDepreciated, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to calculate depreciation: ' . $e->getMessage());
        }
    }
}
