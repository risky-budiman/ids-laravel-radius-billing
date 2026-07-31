<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$incomeAccounts = App\Models\ChartOfAccount::where('type', 'income')->pluck('id');
$items = App\Models\JournalItem::whereIn('account_id', $incomeAccounts)->get();

echo "=== Income Journal Items ===\n";
echo "Count: " . $items->count() . "\n";
echo "Total Credit: Rp " . number_format($items->sum('credit'), 0, ',', '.') . "\n";
echo "Total Debit: Rp " . number_format($items->sum('debit'), 0, ',', '.') . "\n";
echo "Net Revenue: Rp " . number_format($items->sum('credit') - $items->sum('debit'), 0, ',', '.') . "\n\n";

foreach ($items as $i) {
    $j = App\Models\Journal::find($i->journal_id);
    $a = App\Models\ChartOfAccount::find($i->account_id);
    echo sprintf("[%s] %s %s | D:%s C:%s | %s\n",
        $j->reference, $a->code, $a->name,
        number_format($i->debit), number_format($i->credit), $j->date
    );
}
