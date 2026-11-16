<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Password, enable password & community SNMP OLT disimpan terenkripsi (APP_KEY).
 * Kolom diperlebar ke TEXT karena hasil enkripsi > 255 karakter.
 */
return new class extends Migration
{
    private const COLUMNS = ['password', 'enable_password', 'snmp_read_community', 'snmp_write_community'];

    public function up(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            foreach (self::COLUMNS as $col) {
                $table->text($col)->nullable()->default(null)->change();
            }
        });

        foreach (DB::table('olts')->get() as $row) {
            $update = [];
            foreach (self::COLUMNS as $col) {
                $value = $row->{$col};
                if ($value === null || $value === '' || $this->isEncrypted($value)) {
                    continue;
                }
                $update[$col] = Crypt::encryptString($value);
            }
            if ($update) {
                DB::table('olts')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('olts')->get() as $row) {
            $update = [];
            foreach (self::COLUMNS as $col) {
                $value = $row->{$col};
                if ($value !== null && $value !== '' && $this->isEncrypted($value)) {
                    $update[$col] = Crypt::decryptString($value);
                }
            }
            if ($update) {
                DB::table('olts')->where('id', $row->id)->update($update);
            }
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
