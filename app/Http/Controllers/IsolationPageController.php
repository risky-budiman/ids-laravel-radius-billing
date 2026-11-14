<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\IsolationService;
use Illuminate\Http\Request;

/**
 * Halaman tujuan redirect pelanggan terisolir (publik).
 *
 * Pada mode rentang IP, setiap pelanggan terisolir memegang IP isolir unik, sehingga
 * pelanggan dikenali dari IP sumber permintaan dan tagihannya bisa langsung ditampilkan.
 */
class IsolationPageController extends Controller
{
    public function __invoke(Request $request, IsolationService $isolation)
    {
        $customer = $isolation->customerForIp($this->clientIp($request));

        $invoices = $customer
            ? Invoice::where('customer_id', $customer->id)->where('status', 'unpaid')->orderBy('due_date')->get()
            : collect();

        return response()
            ->view('isolation.page', [
                'customer' => $customer,
                'invoices' => $invoices,
                'companyName' => get_setting('company_name', config('app.name')),
                'companyPhone' => get_setting('company_phone'),
                'companyLogo' => get_setting('company_logo'),
            ])
            // Jangan di-cache browser/proxy: setelah lunas pelanggan harus melihat kondisi terbaru.
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * IP pelanggan yang sebenarnya. Aplikasi mempercayai semua proxy (trustProxies '*'),
     * sehingga $request->ip() bisa dipalsukan lewat header X-Forwarded-For. Di sini header
     * hanya dipakai bila koneksi datang dari proxy lokal, dan diambil entri TERAKHIR
     * (yang ditambahkan proxy kita), bukan entri pertama yang bisa diisi klien.
     */
    private function clientIp(Request $request): ?string
    {
        $peer = $request->server('REMOTE_ADDR');
        if (! in_array($peer, ['127.0.0.1', '::1'], true)) {
            return $peer;
        }

        $forwarded = array_filter(array_map('trim', explode(',', (string) $request->header('X-Forwarded-For'))));

        return $forwarded ? end($forwarded) : $peer;
    }
}
