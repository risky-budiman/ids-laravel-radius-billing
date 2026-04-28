<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$customers = Customer::whereNotNull('user_id')->latest()->limit(5)->get();

if ($customers->isEmpty()) {
    echo "BELUM ADA PELANGGAN YANG TERHUBUNG KE USER.\n";
    echo "Silakan ke menu Subscribers > Pilih Detail Pelanggan > Klik 'Create Account' di sidebar kanan.\n";
} else {
    echo "DAFTAR AKUN LOGIN PELANGGAN (Dapat digunakan):\n";
    echo "==============================================\n";
    foreach($customers as $c) {
        echo "Nama: " . $c->name . "\n";
        echo "Login (Bisa pakai salah satu):\n";
        echo "  - ID Pelanggan: " . $c->customer_code . "\n";
        echo "  - Username    : " . $c->username . "\n";
        echo "  - Email       : " . ($c->email ?: 'Kosong (Pakai ID/User)') . "\n";
        echo "Password (Default): " . ($c->phone ?: '12345678') . "\n";
        echo "----------------------------------------------\n";
    }
}
