<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASSETS (1xxx)
            ['code' => '1000', 'name' => 'ASET', 'type' => 'asset', 'parent_id' => null],
            ['code' => '1001', 'name' => 'Kas & Bank', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1101', 'name' => 'Kas Tunai', 'type' => 'asset', 'parent_id' => '1001'],
            ['code' => '1102', 'name' => 'Bank BCA', 'type' => 'asset', 'parent_id' => '1001'],
            ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'asset', 'parent_id' => '1001'],
            ['code' => '1104', 'name' => 'Piutang Pelanggan', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1105', 'name' => 'Persediaan Barang', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1106', 'name' => 'PPN Masukan', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1201', 'name' => 'Inventaris Kantor', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1202', 'name' => 'Peralatan Jaringan (OLT/Router)', 'type' => 'asset', 'parent_id' => '1000'],

            // LIABILITIES (2xxx)
            ['code' => '2000', 'name' => 'KEWAJIBAN', 'type' => 'liability', 'parent_id' => null],
            ['code' => '2101', 'name' => 'Hutang Vendor', 'type' => 'liability', 'parent_id' => '2000'],
            ['code' => '2102', 'name' => 'Uang Muka Pelanggan', 'type' => 'liability', 'parent_id' => '2000'],
            ['code' => '2103', 'name' => 'Hutang Pajak (PPN)', 'type' => 'liability', 'parent_id' => '2000'],

            // EQUITY (3xxx)
            ['code' => '3000', 'name' => 'EKUITAS', 'type' => 'equity', 'parent_id' => null],
            ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity', 'parent_id' => '3000'],
            ['code' => '3200', 'name' => 'Laba Ditahan', 'type' => 'equity', 'parent_id' => '3000'],

            // INCOME (4xxx)
            ['code' => '4000', 'name' => 'PENDAPATAN', 'type' => 'income', 'parent_id' => null],
            ['code' => '4101', 'name' => 'Pendapatan Internet Bulanan', 'type' => 'income', 'parent_id' => '4000'],
            ['code' => '4102', 'name' => 'Pendapatan Instalasi', 'type' => 'income', 'parent_id' => '4000'],
            ['code' => '4103', 'name' => 'Pendapatan Voucher / Hotspot', 'type' => 'income', 'parent_id' => '4000'],
            ['code' => '4104', 'name' => 'Pendapatan Denda Keterlambatan', 'type' => 'income', 'parent_id' => '4000'],
            ['code' => '4105', 'name' => 'Diskon Penjualan (Contra Income)', 'type' => 'income', 'parent_id' => '4000'],
            ['code' => '4199', 'name' => 'Pendapatan Lain-lain', 'type' => 'income', 'parent_id' => '4000'],

            // EXPENSES (5xxx)
            ['code' => '5000', 'name' => 'BEBAN / PENGELUARAN', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5101', 'name' => 'Beban Bandwidth', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5102', 'name' => 'Beban Gaji', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5103', 'name' => 'Beban Listrik', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5104', 'name' => 'Beban Sewa', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5105', 'name' => 'Beban Operasional Lapangan', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5106', 'name' => 'Beban Maintenance Jaringan', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5107', 'name' => 'Beban Admin Bank', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5108', 'name' => 'Beban Transfer Bank', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5109', 'name' => 'Beban Payment Gateway', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5201', 'name' => 'Beban Penyusutan', 'type' => 'expense', 'parent_id' => '5000'],
        ];

        foreach ($accounts as $account) {
            $parentId = null;
            if ($account['parent_id']) {
                $parent = ChartOfAccount::where('code', $account['parent_id'])->first();
                $parentId = $parent ? $parent->id : null;
            }

            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'parent_id' => $parentId,
                ]
            );
        }
    }
}
