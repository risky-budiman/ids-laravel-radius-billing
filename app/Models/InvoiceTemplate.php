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
    public function render(Invoice $invoice): string
    {
        $html = strtr($this->html_content, static::replacementsFor($invoice));

        if ($this->css_content) {
            $html = '<style>' . $this->css_content . '</style>' . $html;
        }

        return $html;
    }

    /**
     * Daftar placeholder => nilai untuk sebuah invoice.
     * Null-safe: invoice tanpa periode/pelanggan/pajak tetap bisa dirender.
     */
    public static function replacementsFor(Invoice $invoice): array
    {
        $invoice->loadMissing(['customer.package', 'tax']);
        $customer = $invoice->customer;

        $company = static::companyReplacements();
        $date = fn ($d, $fmt = 'd M Y') => $d ? $d->locale('id')->translatedFormat($fmt) : '-';
        $num = fn ($v) => number_format((float) $v, 0, ',', '.');

        $isPaid = $invoice->status === 'paid';
        $isOverdue = ! $isPaid && $invoice->due_date && $invoice->due_date->isPast();
        $subtotal = $invoice->subtotal ?: $invoice->amount;
        $taxName = $invoice->tax
            ? trim(($invoice->tax->name ?? 'PPN') . ' ' . static::percent($invoice->tax->rate ?? null))
            : 'Pajak';
        $discountLabel = $invoice->discount_type === 'percentage'
            ? 'Diskon (' . static::percent($invoice->discount_value) . ')'
            : 'Diskon';
        $payUrl = url('/pay/' . $invoice->invoice_number);

        [$statusLabel, $statusBg, $statusFg] = $isPaid
            ? ['Lunas', '#dcfce7', '#166534']
            : ($isOverdue ? ['Lewat jatuh tempo', '#fee2e2', '#991b1b'] : ['Belum dibayar', '#fef3c7', '#92400e']);

        return $company + [
            // Invoice
            '{nomor_invoice}' => e($invoice->invoice_number),
            '{tanggal_invoice}' => $date($invoice->created_at),
            '{tanggal_jatuh_tempo}' => $date($invoice->due_date),
            '{tanggal_bayar}' => $date($invoice->paid_at),
            '{periode_awal}' => $date($invoice->period_start),
            '{periode_akhir}' => $date($invoice->period_end),
            '{periode_tagihan}' => e($invoice->billing_period ?? ''),
            '{status}' => strtoupper((string) $invoice->status),
            '{status_label}' => $statusLabel,
            '{status_badge}' => '<span style="display:inline-block;background:' . $statusBg . ';color:' . $statusFg . ';padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px;">' . $statusLabel . '</span>',
            '{catatan}' => e($invoice->notes ?? ''),
            '{link_bayar}' => e($payUrl),
            '{info_pembayaran}' => $isPaid
                ? '<div style="padding:12px 14px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:12.5px;">Pembayaran telah diterima' . ($invoice->paid_at ? ' pada ' . $date($invoice->paid_at, 'd F Y, H:i') : '') . '. Terima kasih.</div>'
                : '<div style="padding:12px 14px;border-radius:8px;background:#eef2ff;border:1px dashed #c7d2fe;color:#3730a3;font-size:12.5px;">Bayar online sebelum ' . $date($invoice->due_date, 'd F Y') . ' melalui:<br><strong style="word-break:break-all;">' . e($payUrl) . '</strong></div>',

            // Pelanggan
            '{nama_pelanggan}' => e($customer->name ?? 'Pelanggan'),
            '{id_pelanggan}' => e($customer ? ($customer->customer_code ?: $customer->username) : '-'),
            '{alamat_pelanggan}' => e($customer?->address ?: '-'),
            '{telepon_pelanggan}' => e($customer?->phone ?: '-'),
            '{username_pelanggan}' => e($customer?->username ?: '-'),
            '{paket_pelanggan}' => e($customer?->package?->name ?: '-'),
            // Alamat & telepon yang terisi saja, dipisah baris (kosong bila keduanya kosong)
            '{kontak_pelanggan}' => collect([$customer?->address, $customer?->phone])->filter()->map(fn ($v) => e($v))->implode('<br>'),

            // Nominal
            '{subtotal}' => $num($subtotal),
            '{nama_pajak}' => e($taxName),
            '{jumlah_pajak}' => $num($invoice->tax_amount),
            '{diskon}' => $num($invoice->discount_amount),
            '{total}' => $num($invoice->amount),
            '{rp_subtotal}' => 'Rp ' . $num($subtotal),
            '{rp_pajak}' => 'Rp ' . $num($invoice->tax_amount),
            '{rp_diskon}' => 'Rp ' . $num($invoice->discount_amount),
            '{rp_total}' => 'Rp ' . $num($invoice->amount),
            '{terbilang}' => e(ucfirst(\App\Services\AccountingService::terbilang($invoice->amount)) . ' rupiah'),
            // Baris tabel yang hanya muncul bila nilainya ada
            '{baris_diskon}' => (float) $invoice->discount_amount > 0
                ? static::totalRow(e($discountLabel), '−Rp ' . $num($invoice->discount_amount), '#047857')
                : '',
            '{baris_pajak}' => (float) $invoice->tax_amount > 0
                ? static::totalRow(e($taxName), 'Rp ' . $num($invoice->tax_amount))
                : '',

            // Utilitas
            '{tanggal_cetak}' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Placeholder data perusahaan (dipakai juga untuk preview di editor).
     */
    public static function companyReplacements(): array
    {
        $name = get_setting('company_name', config('app.name'));
        $address = get_setting('company_address', '');
        $phone = get_setting('company_phone', '');
        $email = get_setting('company_email', '');
        $website = get_setting('company_website', '');
        $logo = get_setting('company_logo');

        return [
            '{nama_perusahaan}' => e($name),
            '{alamat_perusahaan}' => e($address),
            '{telepon_perusahaan}' => e($phone),
            '{email_perusahaan}' => e($email),
            '{npwp_perusahaan}' => e(get_setting('company_tax_id', '')),
            // Gabungan telepon · email · website tanpa pemisah menggantung bila ada yang kosong
            '{kontak_perusahaan}' => e(collect([$phone, $email, $website])->filter()->implode(' · ')),
            '{logo_perusahaan}' => $logo
                ? '<img src="' . asset('storage/' . $logo) . '" alt="' . e($name) . '" style="max-height:52px;max-width:200px;object-fit:contain;display:block;">'
                : '<div style="font-size:20px;font-weight:700;color:#0f172a;">' . e($name) . '</div>',
            // Hanya gambar logo; kosong bila belum ada logo (dipakai bersama {nama_perusahaan})
            '{logo_saja}' => $logo
                ? '<img src="' . asset('storage/' . $logo) . '" alt="' . e($name) . '" style="max-height:52px;max-width:200px;object-fit:contain;display:block;">'
                : '',
            '{catatan_footer}' => e(get_setting('invoice_footer_note', '')),
        ];
    }

    /**
     * Template A4 awal untuk Invoice Builder (inline style agar mandiri saat dicetak).
     */
    public static function starterHtml(): string
    {
        return <<<'HTML'
<div style="font-family: Inter, 'Segoe UI', Arial, sans-serif; color: #334155; font-size: 13px; line-height: 1.55;">
  <!-- Kepala -->
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="vertical-align: top;">
        {logo_saja}
        <div style="margin-top: 8px; font-size: 16px; font-weight: 700; color: #0f172a;">{nama_perusahaan}</div>
        <div style="font-size: 12px; color: #64748b;">{alamat_perusahaan}</div>
        <div style="font-size: 12px; color: #64748b;">{kontak_perusahaan}</div>
      </td>
      <td style="vertical-align: top; text-align: right;">
        <div style="font-size: 28px; font-weight: 700; color: #0f172a; letter-spacing: -0.02em;">Invoice</div>
        <div style="font-size: 13px; color: #64748b; margin: 2px 0 10px; white-space: nowrap;">{nomor_invoice}</div>
        {status_badge}
      </td>
    </tr>
  </table>

  <!-- Info -->
  <table style="width: 100%; border-collapse: separate; margin: 28px 0 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
    <tr>
      <td style="vertical-align: top; padding: 16px 18px; width: 55%;">
        <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;">Ditagihkan kepada</div>
        <div style="font-size: 15px; font-weight: 600; color: #0f172a;">{nama_pelanggan}</div>
        <div style="color: #64748b;">ID Pelanggan: {id_pelanggan}</div>
        <div style="color: #64748b;">{kontak_pelanggan}</div>
      </td>
      <td style="vertical-align: top; padding: 16px 18px;">
        <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;">Detail invoice</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
          <tr><td style="color: #64748b; padding: 2px 0;">Tanggal terbit</td><td style="text-align: right; font-weight: 500; color: #0f172a; white-space: nowrap;">{tanggal_invoice}</td></tr>
          <tr><td style="color: #64748b; padding: 2px 0;">Jatuh tempo</td><td style="text-align: right; font-weight: 500; color: #0f172a; white-space: nowrap;">{tanggal_jatuh_tempo}</td></tr>
          <tr><td style="color: #64748b; padding: 2px 0;">Periode</td><td style="text-align: right; font-weight: 500; color: #0f172a; white-space: nowrap;">{periode_awal} – {periode_akhir}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Rincian -->
  <table style="width: 100%; border-collapse: collapse;">
    <thead>
      <tr>
        <th style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #cbd5e1; width: 6%;">No</th>
        <th style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #cbd5e1;">Deskripsi</th>
        <th style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #cbd5e1; width: 10%;">Qty</th>
        <th style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #cbd5e1; width: 24%;">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top;">1</td>
        <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
          <div style="font-weight: 600; color: #0f172a;">Layanan Internet – {paket_pelanggan}</div>
          <div style="font-size: 12px; color: #94a3b8;">Berlangganan {periode_tagihan} · {periode_awal} – {periode_akhir}</div>
          <div style="font-size: 12px; color: #94a3b8;">{catatan}</div>
        </td>
        <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9; text-align: right; vertical-align: top;">1</td>
        <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9; text-align: right; vertical-align: top; white-space: nowrap;">{rp_subtotal}</td>
      </tr>
    </tbody>
  </table>

  <!-- Total -->
  <table style="width: 340px; margin: 16px 0 0 auto; border-collapse: collapse; font-size: 13px;">
    <tr><td style="padding: 6px 0; color: #64748b;">Subtotal</td><td style="padding: 6px 0; text-align: right; white-space: nowrap;">{rp_subtotal}</td></tr>
    {baris_diskon}
    {baris_pajak}
    <tr>
      <td style="padding: 12px 14px; background: #0f172a; color: #cbd5e1; font-size: 12px; border-radius: 8px 0 0 8px;">Total</td>
      <td style="padding: 12px 14px; background: #0f172a; color: #ffffff; font-size: 18px; font-weight: 700; text-align: right; white-space: nowrap; border-radius: 0 8px 8px 0;">{rp_total}</td>
    </tr>
    <tr><td colspan="2" style="padding-top: 6px; text-align: right; font-size: 11.5px; font-style: italic; color: #64748b;">{terbilang}</td></tr>
  </table>

  <!-- Pembayaran -->
  <div style="margin-top: 24px;">{info_pembayaran}</div>

  <!-- Kaki -->
  <table style="width: 100%; margin-top: 28px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
    <tr>
      <td style="padding-top: 14px; vertical-align: top; white-space: pre-line;">{catatan_footer}</td>
      <td style="padding-top: 14px; vertical-align: top; text-align: right; white-space: nowrap;">Dokumen elektronik, sah tanpa tanda tangan.<br><span style="color: #94a3b8; font-size: 11px;">Dicetak {tanggal_cetak}</span></td>
    </tr>
  </table>
</div>
HTML;
    }

    protected static function totalRow(string $label, string $value, string $color = '#334155'): string
    {
        return '<tr><td style="padding:6px 0;color:' . $color . ';">' . $label . '</td>'
            . '<td style="padding:6px 0;text-align:right;color:' . $color . ';white-space:nowrap;">' . $value . '</td></tr>';
    }

    protected static function percent($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%';
    }
}
