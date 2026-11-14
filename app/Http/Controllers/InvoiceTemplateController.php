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
        $html = strtr($html, $sampleData);

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
                '{logo_perusahaan}' => 'Logo perusahaan (atau nama bila belum ada logo)',
                '{logo_saja}' => 'Logo saja (kosong bila belum ada logo)',
                '{alamat_perusahaan}' => 'Alamat perusahaan',
                '{kontak_perusahaan}' => 'Telepon · email · website (yang terisi saja)',
                '{telepon_perusahaan}' => 'No. telepon perusahaan',
                '{email_perusahaan}' => 'Email perusahaan',
                '{npwp_perusahaan}' => 'NPWP perusahaan',
                '{catatan_footer}' => 'Catatan bawah invoice dari pengaturan',
            ],
            'Invoice' => [
                '{nomor_invoice}' => 'Nomor invoice',
                '{tanggal_invoice}' => 'Tanggal terbit',
                '{tanggal_jatuh_tempo}' => 'Tanggal jatuh tempo',
                '{tanggal_bayar}' => 'Tanggal pembayaran (bila lunas)',
                '{periode_awal}' => 'Tanggal awal periode',
                '{periode_akhir}' => 'Tanggal akhir periode',
                '{periode_tagihan}' => 'Jenis periode (1 Month, dll)',
                '{status_label}' => 'Status: Lunas / Belum dibayar / Lewat jatuh tempo',
                '{status_badge}' => 'Badge status berwarna (HTML)',
                '{status}' => 'Status mentah (PAID/UNPAID)',
                '{info_pembayaran}' => 'Kotak link bayar online, atau keterangan lunas (HTML)',
                '{link_bayar}' => 'URL pembayaran online',
                '{catatan}' => 'Catatan/notes pada invoice',
            ],
            'Pelanggan' => [
                '{nama_pelanggan}' => 'Nama pelanggan',
                '{id_pelanggan}' => 'ID / kode pelanggan',
                '{alamat_pelanggan}' => 'Alamat pelanggan',
                '{telepon_pelanggan}' => 'No. telepon pelanggan',
                '{kontak_pelanggan}' => 'Alamat & telepon yang terisi saja (HTML)',
                '{username_pelanggan}' => 'Username PPPoE',
                '{paket_pelanggan}' => 'Nama paket internet',
            ],
            'Nominal' => [
                '{rp_subtotal}' => 'Subtotal dengan prefix Rp',
                '{rp_diskon}' => 'Diskon dengan prefix Rp',
                '{rp_pajak}' => 'Pajak dengan prefix Rp',
                '{rp_total}' => 'Total dengan prefix Rp',
                '{subtotal}' => 'Subtotal tanpa prefix',
                '{diskon}' => 'Diskon tanpa prefix',
                '{jumlah_pajak}' => 'Pajak tanpa prefix',
                '{total}' => 'Total tanpa prefix',
                '{nama_pajak}' => 'Nama dan tarif pajak',
                '{baris_diskon}' => 'Baris tabel diskon — hanya tampil bila ada diskon (HTML <tr>)',
                '{baris_pajak}' => 'Baris tabel pajak — hanya tampil bila ada pajak (HTML <tr>)',
                '{terbilang}' => 'Total dalam kata-kata',
            ],
            'Utilitas' => [
                '{tanggal_cetak}' => 'Tanggal & jam saat dicetak',
            ],
        ];
    }

    /**
     * Data contoh untuk preview editor — memakai mesin render yang sama dengan cetak asli.
     */
    private function getSampleReplacements(): array
    {
        $package = (new \App\Models\Package)->forceFill(['name' => 'Fiber 30 Mbps']);
        $customer = (new \App\Models\Customer)->forceFill([
            'name' => 'Ahmad Budiman',
            'customer_code' => '001001000123',
            'username' => 'ahmad.budiman',
            'address' => 'Jl. Pelanggan No. 456, Bandung',
            'phone' => '0856-1234-5678',
        ]);
        $customer->setRelation('package', $package);

        $tax = (new \App\Models\Tax)->forceFill(['name' => 'PPN', 'rate' => 11]);

        $invoice = (new \App\Models\Invoice)->forceFill([
            'invoice_number' => 'INV-CONTOH-0001',
            'billing_period' => '1 Month',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
            'status' => 'unpaid',
            'subtotal' => 350000,
            'discount_type' => 'fixed',
            'discount_value' => 25000,
            'discount_amount' => 25000,
            'tax_amount' => 35750,
            'amount' => 360750,
            'notes' => null,
        ]);
        $invoice->created_at = now();
        $invoice->setRelation('customer', $customer);
        $invoice->setRelation('tax', $tax);

        return InvoiceTemplate::replacementsFor($invoice);
    }

    private function getDefaultHtml(): string
    {
        return InvoiceTemplate::starterHtml();
    }
}
