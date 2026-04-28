<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$customers = Customer::limit(5)->get();

if ($customers->isEmpty()) {
    echo "DATABASE PELANGGAN KOSONG. Silakan tambah pelanggan dulu di Admin.\n";
} else {
    echo "CONTOH DATA LOGIN PELANGGAN:\n";
    echo "============================\n";
    foreach($customers as $c) {
        echo "Nama     : " . $c->name . "\n";
        echo "ID       : " . $c->customer_code . "\n";
        echo "User     : " . $c->username . "\n";
        echo "No HP    : " . ($c->phone ?: '(KOSONG - Harus diisi di Admin)') . "\n";
        echo "----------------------------\n";
    }
}
