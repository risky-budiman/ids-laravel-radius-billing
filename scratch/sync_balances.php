<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting balance synchronization...\n";

DB::transaction(function() {
    $accounts = BankAccount::all();
    foreach($accounts as $account) {
        $transactions = BankTransaction::where('bank_account_id', $account->id)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $balance = '0.00';
        foreach($transactions as $tx) {
            // Ensure tx amount is also formatted to 2 decimals string
            $amount = number_format($tx->amount, 2, '.', '');
            
            if ($tx->type == 'deposit') {
                $balance = bcadd($balance, $amount, 2);
            } else {
                $balance = bcsub($balance, $amount, 2);
            }
        }
        
        $account->balance = $balance;
        $account->save();
        echo "Updated Account [{$account->id}] {$account->bank_name}: New Balance = {$balance}\n";
    }
});

echo "Synchronization complete.\n";
