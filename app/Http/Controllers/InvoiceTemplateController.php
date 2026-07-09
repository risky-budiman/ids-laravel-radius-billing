<?php

namespace App\Http\Controllers;

use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;

class InvoiceTemplateController extends Controller
{
    public function index()
    {
        $templates = InvoiceTemplate::orderBy('format')->orderBy('name')->get();
        return view('invoice-templates.index', compact('templates'));
    }

    public function create()
    {
        $variables = $this->getAvailableVariables();
        return view('invoice-templates.editor', [
            'template' => null,
            'variables' => $variables,
            'defaultHtml' => $this->getDefaultHtml(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|in:A4,Thermal',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $template = InvoiceTemplate::create($validated);

        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        }

        return redirect()->route('invoice-templates.index')
            ->with('success', 'Template invoice berhasil dibuat.');
    }

    public function edit(InvoiceTemplate $invoiceTemplate)
    {
        $variables = $this->getAvailableVariables();
        return view('invoice-templates.editor', [
            'template' => $invoiceTemplate,
            'variables' => $variables,
            'defaultHtml' => $this->getDefaultHtml(),
        ]);
    }

    public function update(Request $request, InvoiceTemplate $invoiceTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|in:A4,Thermal',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $invoiceTemplate->update($validated);

        if ($request->boolean('is_default')) {
            $invoiceTemplate->setAsDefault();
        }

        return redirect()->route('invoice-templates.index')
            ->with('success', 'Template invoice berhasil diperbarui.');
    }

    public function destroy(InvoiceTemplate $invoiceTemplate)
    {
        if ($invoiceTemplate->is_default) {
            return back()->with('error', 'Tidak bisa menghapus template default. Atur template lain sebagai default terlebih dahulu.');
        }

        $invoiceTemplate->delete();
        return redirect()->route('invoice-templates.index')
            ->with('success', 'Template invoice berhasil dihapus.');
    }

    public function setDefault(InvoiceTemplate $invoiceTemplate)
    {
        $invoiceTemplate->setAsDefault();
        return back()->with('success', "Template \"{$invoiceTemplate->name}\" telah dijadikan default untuk format {$invoiceTemplate->format}.");
    }

    public function preview(Request $request)
    {
        // Returns rendered HTML for live preview in the editor
        $html = $request->input('html_content', '');
        $css = $request->input('css_content', '');

        // Replace variables with sample data for preview
        $sampleData = $this->getSampleReplacements();
        $html = str_replace(array_keys($sampleData), array_values($sampleData), $html);

        if ($css) {
            $html = '<style>' . $css . '</style>' . $html;
        }

        return response($html);
    }

    private function getAvailableVariables(): array
    {
        return [
            'Perusahaan' => [
                '{nama_perusahaan}' => 'Nama perusahaan dari pengaturan',
                '{alamat_perusahaan}' => 'Alamat perusahaan',
                '{telepon_perusahaan}' => 'No. telepon perusahaan',
                '{email_perusahaan}' => 'Email perusahaan',
                '{logo_perusahaan}' => 'Tag <img> logo perusahaan',
                '{catatan_footer}' => 'Catatan bawah invoice dari pengaturan',
            ],
            'Invoice' => [
                '{nomor_invoice}' => 'Nomor invoice (INV-XXXX)',
                '{tanggal_invoice}' => 'Tanggal pembuatan invoice',
                '{tanggal_jatuh_tempo}' => 'Tanggal jatuh tempo',
                '{periode_awal}' => 'Tanggal awal periode',
                '{periode_akhir}' => 'Tanggal akhir periode',
                '{periode_tagihan}' => 'Jenis periode (1 Month, dll)',
                '{status}' => 'Status invoice (PAID/UNPAID)',
                '{status_badge}' => 'Badge status berwarna (HTML)',
                '{catatan}' => 'Catatan/notes pada invoice',
            ],
            'Pelanggan' => [
                '{nama_pelanggan}' => 'Nama pelanggan',
                '{alamat_pelanggan}' => 'Alamat pelanggan',
                '{telepon_pelanggan}' => 'No. telepon pelanggan',
                '{username_pelanggan}' => 'Username/ID pelanggan',
                '{paket_pelanggan}' => 'Nama paket internet',
            ],
            'Nominal' => [
                '{subtotal}' => 'Subtotal tanpa prefix Rp',
                '{nama_pajak}' => 'Nama dan rate pajak (PPN 11%)',
                '{jumlah_pajak}' => 'Jumlah pajak tanpa prefix Rp',
                '{total}' => 'Total tagihan tanpa prefix Rp',
                '{rp_subtotal}' => 'Subtotal dengan prefix Rp',
                '{rp_pajak}' => 'Jumlah pajak dengan prefix Rp',
                '{rp_total}' => 'Total tagihan dengan prefix Rp',
            ],
            'Utilitas' => [
                '{tanggal_cetak}' => 'Tanggal & jam saat dicetak',
            ],
        ];
    }

    private function getSampleReplacements(): array
    {
        $companyName = get_setting('company_name', config('app.name', 'PT Internet Cepat'));
        return [
            '{nama_perusahaan}' => $companyName,
            '{alamat_perusahaan}' => get_setting('company_address', 'Jl. Contoh No. 123, Jakarta'),
            '{telepon_perusahaan}' => get_setting('company_phone', '0812-3456-7890'),
            '{email_perusahaan}' => get_setting('company_email', 'admin@isp.com'),
            '{logo_perusahaan}' => get_setting('company_logo')
                ? '<img src="' . asset('storage/' . get_setting('company_logo')) . '" style="max-height:64px;object-fit:contain;">'
                : '<h1 style="margin:0;font-size:24px;font-weight:bold;">' . e($companyName) . '</h1>',
            '{catatan_footer}' => get_setting('invoice_footer_note', 'Terima kasih atas kepercayaan Anda.'),
            '{nomor_invoice}' => 'INV-SAMPLE001',
            '{tanggal_invoice}' => now()->format('d M Y'),
            '{tanggal_jatuh_tempo}' => now()->addDays(14)->format('d M Y'),
            '{periode_awal}' => now()->startOfMonth()->format('d M Y'),
            '{periode_akhir}' => now()->endOfMonth()->format('d M Y'),
            '{periode_tagihan}' => '1 Month',
            '{status}' => 'UNPAID',
            '{status_badge}' => '<span style="background:#fee2e2;color:#991b1b;padding:4px 12px;border-radius:4px;font-weight:bold;font-size:12px;">UNPAID</span>',
            '{catatan}' => 'Internet Service Plan - Paket Fiber 30 Mbps',
            '{nama_pelanggan}' => 'Ahmad Budiman',
            '{alamat_pelanggan}' => 'Jl. Pelanggan No. 456, Bandung',
            '{telepon_pelanggan}' => '0856-1234-5678',
            '{username_pelanggan}' => 'ahmad.budiman',
            '{paket_pelanggan}' => 'Fiber 30 Mbps',
            '{subtotal}' => '350.000',
            '{nama_pajak}' => 'PPN 11%',
            '{jumlah_pajak}' => '38.500',
            '{total}' => '388.500',
            '{rp_subtotal}' => 'Rp 350.000',
            '{rp_pajak}' => 'Rp 38.500',
            '{rp_total}' => 'Rp 388.500',
            '{tanggal_cetak}' => now()->format('d/m/Y H:i'),
        ];
    }

    private function getDefaultHtml(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; color: #333; max-width: 800px; margin: 0 auto;">
  <!-- Header -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
    <div>
      {logo_perusahaan}
      <p style="margin: 8px 0 0; font-size: 13px; color: #666; max-width: 280px;">{alamat_perusahaan}</p>
      <p style="margin: 4px 0 0; font-size: 13px; color: #666;">{telepon_perusahaan} | {email_perusahaan}</p>
    </div>
    <div style="text-align: right;">
      <h2 style="margin: 0; font-size: 32px; color: #ccc; letter-spacing: 4px;">INVOICE</h2>
      <p style="margin: 8px 0; font-size: 16px; font-weight: bold;">{nomor_invoice}</p>
      {status_badge}
    </div>
  </div>

  <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

  <!-- Info -->
  <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
    <div>
      <p style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Billed To:</p>
      <p style="font-size: 16px; font-weight: bold; margin: 0;">{nama_pelanggan}</p>
      <p style="font-size: 13px; color: #666; margin: 4px 0;">{alamat_pelanggan}</p>
      <p style="font-size: 13px; color: #666; margin: 0;">{telepon_pelanggan}</p>
    </div>
    <div style="text-align: right;">
      <table style="font-size: 13px; margin-left: auto;">
        <tr><td style="color: #999; padding: 4px 16px 4px 0;">Tanggal Invoice:</td><td style="font-weight: bold;">{tanggal_invoice}</td></tr>
        <tr><td style="color: #999; padding: 4px 16px 4px 0;">Jatuh Tempo:</td><td style="font-weight: bold;">{tanggal_jatuh_tempo}</td></tr>
        <tr><td style="color: #999; padding: 4px 16px 4px 0;">Periode:</td><td style="font-weight: bold;">{periode_awal} - {periode_akhir}</td></tr>
      </table>
    </div>
  </div>

  <!-- Items Table -->
  <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
    <thead>
      <tr style="border-top: 2px solid #333; border-bottom: 2px solid #333;">
        <th style="padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; color: #333;">No</th>
        <th style="padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; color: #333;">Deskripsi</th>
        <th style="padding: 10px; text-align: center; font-size: 12px; text-transform: uppercase; color: #333;">Qty</th>
        <th style="padding: 10px; text-align: right; font-size: 12px; text-transform: uppercase; color: #333;">Jumlah (Rp)</th>
      </tr>
    </thead>
    <tbody>
      <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="padding: 12px 10px;">1</td>
        <td style="padding: 12px 10px;">
          <strong>Internet Service ({periode_tagihan})</strong><br>
          <span style="font-size: 12px; color: #888;">{catatan}</span>
        </td>
        <td style="padding: 12px 10px; text-align: center;">1</td>
        <td style="padding: 12px 10px; text-align: right; font-weight: bold;">{subtotal}</td>
      </tr>
    </tbody>
  </table>

  <!-- Totals -->
  <div style="display: flex; justify-content: flex-end; margin-bottom: 40px;">
    <table style="width: 280px; font-size: 14px;">
      <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="padding: 8px 0; color: #666;">Subtotal</td>
        <td style="padding: 8px 0; text-align: right; font-weight: bold;">{rp_subtotal}</td>
      </tr>
      <tr style="border-bottom: 1px solid #e5e7eb;">
        <td style="padding: 8px 0; color: #666;">Pajak ({nama_pajak})</td>
        <td style="padding: 8px 0; text-align: right; font-weight: bold;">{rp_pajak}</td>
      </tr>
      <tr style="background: #f3f4f6;">
        <td style="padding: 12px 8px; font-weight: bold; font-size: 15px;">Total</td>
        <td style="padding: 12px 8px; text-align: right; font-weight: bold; font-size: 15px;">{rp_total}</td>
      </tr>
    </table>
  </div>

  <!-- Footer -->
  <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center;">
    <p style="font-size: 12px; color: #888; white-space: pre-line;">{catatan_footer}</p>
    <p style="font-size: 10px; color: #bbb; margin-top: 12px;">Dicetak pada {tanggal_cetak}</p>
  </div>
</div>
HTML;
    }
}
