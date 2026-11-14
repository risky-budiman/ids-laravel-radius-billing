<?php

use App\Models\InvoiceTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbarui template invoice yang masih PERSIS sama dengan template awal lama
 * Invoice Builder (belum pernah diedit) ke template awal baru.
 * Template yang sudah diubah pengguna tidak disentuh.
 */
return new class extends Migration
{
    /** md5 dari template awal lama (trim, newline LF). */
    private const OLD_STARTER_MD5 = '073aa9513174506b223d0995e19d4cb9';

    public function up(): void
    {
        $normalize = fn (?string $html) => trim(str_replace("\r\n", "\n", (string) $html));

        DB::table('invoice_templates')
            ->select(['id', 'html_content', 'css_content'])
            ->orderBy('id')
            ->get()
            ->each(function ($tpl) use ($normalize) {
                if (md5($normalize($tpl->html_content)) === self::OLD_STARTER_MD5 && blank($tpl->css_content)) {
                    DB::table('invoice_templates')
                        ->where('id', $tpl->id)
                        ->update(['html_content' => InvoiceTemplate::starterHtml(), 'updated_at' => now()]);
                }
            });
    }

    public function down(): void
    {
        // Tidak dikembalikan: template lama tetap ada di riwayat git (InvoiceTemplateController::getDefaultHtml).
    }
};
