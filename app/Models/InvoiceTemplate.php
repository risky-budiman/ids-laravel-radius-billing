<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'name',
        'format',
        'is_default',
        'html_content',
        'css_content',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the default template for a given format.
     */
    public static function getDefault(string $format = 'A4'): ?self
    {
        return static::where('format', $format)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Set this template as the default for its format, unsetting others.
     */
    public function setAsDefault(): void
    {
        static::where('format', $this->format)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Render this template with invoice data.
     * Replaces placeholder variables like {nama_pelanggan} with actual data.
     */
    public function render(\App\Models\Invoice $invoice): string
    {
        $invoice->loadMissing(['customer.package', 'tax']);

        $companyName = get_setting('company_name', config('app.name'));
        $companyAddress = get_setting('company_address', '');
        $companyPhone = get_setting('company_phone', '');
        $companyEmail = get_setting('company_email', '');
        $companyLogo = get_setting('company_logo');
        $footerNote = get_setting('invoice_footer_note', '');

        $logoHtml = $companyLogo
            ? '<img src="' . asset('storage/' . $companyLogo) . '" alt="' . e($companyName) . '" style="max-height:64px;object-fit:contain;">'
            : '<h1 style="margin:0;font-size:24px;font-weight:bold;">' . e($companyName) . '</h1>';

        $replacements = [
            // Company
            '{nama_perusahaan}' => e($companyName),
            '{alamat_perusahaan}' => e($companyAddress),
            '{telepon_perusahaan}' => e($companyPhone),
            '{email_perusahaan}' => e($companyEmail),
            '{logo_perusahaan}' => $logoHtml,
            '{catatan_footer}' => e($footerNote),

            // Invoice
            '{nomor_invoice}' => e($invoice->invoice_number),
            '{tanggal_invoice}' => $invoice->created_at->format('d M Y'),
            '{tanggal_jatuh_tempo}' => $invoice->due_date->format('d M Y'),
            '{periode_awal}' => $invoice->period_start->format('d M Y'),
            '{periode_akhir}' => $invoice->period_end->format('d M Y'),
            '{periode_tagihan}' => e($invoice->billing_period),
            '{status}' => strtoupper($invoice->status),
            '{status_badge}' => $invoice->status == 'paid'
                ? '<span style="background:#dcfce7;color:#166534;padding:4px 12px;border-radius:4px;font-weight:bold;font-size:12px;text-transform:uppercase;">PAID</span>'
                : '<span style="background:#fee2e2;color:#991b1b;padding:4px 12px;border-radius:4px;font-weight:bold;font-size:12px;text-transform:uppercase;">UNPAID</span>',
            '{catatan}' => e($invoice->notes ?? ''),

            // Customer
            '{nama_pelanggan}' => e($invoice->customer->name ?? 'Unknown'),
            '{alamat_pelanggan}' => e($invoice->customer->address ?? '-'),
            '{telepon_pelanggan}' => e($invoice->customer->phone ?? '-'),
            '{username_pelanggan}' => e($invoice->customer->username ?? '-'),
            '{paket_pelanggan}' => e($invoice->customer->package->name ?? '-'),

            // Amounts
            '{subtotal}' => number_format($invoice->subtotal, 0, ',', '.'),
            '{nama_pajak}' => e(($invoice->tax->name ?? 'PPN') . ' ' . ($invoice->tax->rate ?? 0) . '%'),
            '{jumlah_pajak}' => number_format($invoice->tax_amount, 0, ',', '.'),
            '{total}' => number_format($invoice->amount, 0, ',', '.'),
            '{rp_subtotal}' => 'Rp ' . number_format($invoice->subtotal, 0, ',', '.'),
            '{rp_pajak}' => 'Rp ' . number_format($invoice->tax_amount, 0, ',', '.'),
            '{rp_total}' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),

            // Utility
            '{tanggal_cetak}' => now()->format('d/m/Y H:i'),
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $this->html_content);

        // Wrap with CSS if present
        if ($this->css_content) {
            $html = '<style>' . $this->css_content . '</style>' . $html;
        }

        return $html;
    }
}
