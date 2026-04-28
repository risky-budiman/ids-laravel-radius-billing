<?php

use App\Models\ChartOfAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Shift existing accounts that overlap with new codes
            // Old 1105 (PPN Masukan) -> New 1106
            $ppn = ChartOfAccount::where('code', '1105')->first();
            if ($ppn) $ppn->update(['code' => '1106_TEMP']);

            // Old 1104 (Persediaan) -> New 1105
            $stock = ChartOfAccount::where('code', '1104')->first();
            if ($stock) $stock->update(['code' => '1105_TEMP']);

            // Old 1103 (Piutang) -> New 1104
            $piutang = ChartOfAccount::where('code', '1103')->first();
            if ($piutang) $piutang->update(['code' => '1104_TEMP']);

            // Now clean up TEMP codes to final new codes
            ChartOfAccount::where('code', '1106_TEMP')->update(['code' => '1106', 'name' => 'PPN Masukan']);
            ChartOfAccount::where('code', '1105_TEMP')->update(['code' => '1105', 'name' => 'Persediaan Barang']);
            ChartOfAccount::where('code', '1104_TEMP')->update(['code' => '1104', 'name' => 'Piutang Pelanggan']);

            // 2. Add or Update all accounts based on the new standard
            // This is similar to the seeder but done in a migration to ensure it runs in production
            $standard = [
                ['code' => '1000', 'name' => 'ASET', 'type' => 'asset', 'parent' => null],
                ['code' => '1001', 'name' => 'Kas & Bank', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '1101', 'name' => 'Kas Tunai', 'type' => 'asset', 'parent' => '1001'],
                ['code' => '1102', 'name' => 'Bank BCA', 'type' => 'asset', 'parent' => '1001'],
                ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'asset', 'parent' => '1001'],
                ['code' => '1104', 'name' => 'Piutang Pelanggan', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '1105', 'name' => 'Persediaan Barang', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '1106', 'name' => 'PPN Masukan', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '1201', 'name' => 'Inventaris Kantor', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '1202', 'name' => 'Peralatan Jaringan (OLT/Router)', 'type' => 'asset', 'parent' => '1000'],
                ['code' => '2000', 'name' => 'KEWAJIBAN', 'type' => 'liability', 'parent' => null],
                ['code' => '2101', 'name' => 'Hutang Vendor', 'type' => 'liability', 'parent' => '2000'],
                ['code' => '2102', 'name' => 'Uang Muka Pelanggan', 'type' => 'liability', 'parent' => '2000'],
                ['code' => '2103', 'name' => 'Hutang Pajak (PPN)', 'type' => 'liability', 'parent' => '2000'],
                ['code' => '3000', 'name' => 'EKUITAS', 'type' => 'equity', 'parent' => null],
                ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity', 'parent' => '3000'],
                ['code' => '3200', 'name' => 'Laba Ditahan', 'type' => 'equity', 'parent' => '3000'],
                ['code' => '4000', 'name' => 'PENDAPATAN', 'type' => 'income', 'parent' => null],
                ['code' => '4101', 'name' => 'Pendapatan Internet Bulanan', 'type' => 'income', 'parent' => '4000'],
                ['code' => '4102', 'name' => 'Pendapatan Instalasi', 'type' => 'income', 'parent' => '4000'],
                ['code' => '4103', 'name' => 'Pendapatan Voucher / Hotspot', 'type' => 'income', 'parent' => '4000'],
                ['code' => '4104', 'name' => 'Pendapatan Denda Keterlambatan', 'type' => 'income', 'parent' => '4000'],
                ['code' => '4105', 'name' => 'Diskon Penjualan (Contra Income)', 'type' => 'income', 'parent' => '4000'],
                ['code' => '4199', 'name' => 'Pendapatan Lain-lain', 'type' => 'income', 'parent' => '4000'],
                ['code' => '5000', 'name' => 'BEBAN / PENGELUARAN', 'type' => 'expense', 'parent' => null],
                ['code' => '5101', 'name' => 'Beban Bandwidth', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5102', 'name' => 'Beban Gaji', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5103', 'name' => 'Beban Listrik', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5104', 'name' => 'Beban Sewa', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5105', 'name' => 'Beban Operasional Lapangan', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5106', 'name' => 'Beban Maintenance Jaringan', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5107', 'name' => 'Beban Admin Bank', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5108', 'name' => 'Beban Transfer Bank', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5109', 'name' => 'Beban Payment Gateway', 'type' => 'expense', 'parent' => '5000'],
                ['code' => '5201', 'name' => 'Beban Penyusutan', 'type' => 'expense', 'parent' => '5000'],
            ];

            foreach ($standard as $acc) {
                $parentId = null;
                if ($acc['parent']) {
                    $parentModel = ChartOfAccount::where('code', $acc['parent'])->first();
                    $parentId = $parentModel?->id;
                }

                ChartOfAccount::updateOrCreate(
                    ['code' => $acc['code']],
                    [
                        'name' => $acc['name'],
                        'type' => $acc['type'],
                        'parent_id' => $parentId
                    ]
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing CoA changes in production is risky and usually not needed.
    }
};
