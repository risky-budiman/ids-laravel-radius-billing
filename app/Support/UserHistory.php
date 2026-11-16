<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mencari data yang merujuk ke akun staf (tiket, komisi, jurnal, log, pelanggan referal, dst.).
 * Akun yang punya riwayat tidak boleh dihapus permanen — cukup dinonaktifkan — agar riwayat tidak
 * terputus atau ikut terhapus (beberapa FK memakai cascade).
 */
class UserHistory
{
    /** Kolom yang merujuk ke users tanpa foreign key. */
    private const EXTRA_COLUMNS = [
        ['purchase_orders', 'created_by'],
    ];

    /** Tabel teknis yang bukan riwayat. */
    private const IGNORED_TABLES = ['sessions', 'password_reset_tokens', 'personal_access_tokens'];

    /**
     * @return array<string, int> nama tabel => jumlah baris yang merujuk ke akun ini
     */
    public static function references(User $user): array
    {
        $columns = self::EXTRA_COLUMNS;
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if (in_array($name, self::IGNORED_TABLES, true)) {
                continue;
            }
            foreach (Schema::getForeignKeys($name) as $fk) {
                if ($fk['foreign_table'] === 'users' && count($fk['columns']) === 1) {
                    $columns[] = [$name, $fk['columns'][0]];
                }
            }
        }

        $found = [];
        foreach ($columns as [$table, $column]) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }
            $count = DB::table($table)->where($column, $user->getKey())->count();
            if ($count > 0) {
                $found[$table] = ($found[$table] ?? 0) + $count;
            }
        }

        return $found;
    }
}
