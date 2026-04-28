<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$c = Customer::first();

if (!$c) {
    die("ERROR: Tidak ada data pelanggan di database.\n");
}

$email = $c->email ?: ($c->username . "@radius.local");
$passwordRaw = $c->phone ?: "12345678";

$user = User::updateOrCreate(
    ['email' => $email],
    [
        'name' => $c->name,
        'password' => Hash::make($passwordRaw),
        'role' => User::ROLE_CUSTOMER,
        'is_active' => true,
    ]
);

$c->update(['user_id' => $user->id]);

echo "SUCCESS! Akun Portal Pelanggan Berhasil Dibuat.\n";
echo "Silakan gunakan data berikut untuk login:\n";
echo "==========================================\n";
echo "ID Pelanggan : " . $c->customer_code . "\n";
echo "Username     : " . $c->username . "\n";
echo "Password     : " . $passwordRaw . "\n";
echo "==========================================\n";
