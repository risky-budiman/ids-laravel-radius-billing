<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\PortalOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Masuk portal pelanggan: ID pelanggan + nomor HP terdaftar, lalu (bila diaktifkan) kode OTP yang
 * dikirim ke WhatsApp nomor terdaftar. Tanpa OTP, siapa pun yang tahu ID (tercetak di invoice) dan
 * nomor HP pelanggan bisa masuk.
 */
class LoginController extends Controller
{
    private const SESSION_KEY = 'portal_otp_customer';

    public function __construct(private PortalOtpService $otp)
    {
    }

    public function showLoginForm()
    {
        return view('customer-portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
        ]);

        $customer = $this->findCustomer(trim($request->customer_id), trim($request->phone));
        if (! $customer) {
            return back()->withInput($request->only('customer_id', 'phone'))
                ->with('error', 'Data pelanggan tidak ditemukan atau nomor HP tidak cocok.');
        }
        if (in_array($customer->status, PortalOtpService::BLOCKED_STATUSES, true)) {
            return back()->withInput($request->only('customer_id'))
                ->with('error', 'Akun pelanggan ini telah berhenti berlangganan. Silakan hubungi Admin.');
        }

        if (! PortalOtpService::enabled()) {
            return $this->signIn($request, $customer);
        }

        try {
            $this->otp->send($customer);
        } catch (\DomainException $e) {
            // Kode sebelumnya masih berlaku -> tetap ke halaman verifikasi
            if ($request->session()->get(self::SESSION_KEY . '.id') === $customer->id) {
                return redirect()->route('customer.login.verify')->with('error', $e->getMessage());
            }

            return back()->withInput($request->only('customer_id', 'phone'))->with('error', $e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, ['id' => $customer->id, 'remember' => $request->boolean('remember')]);

        return redirect()->route('customer.login.verify')
            ->with('success', 'Kode dikirim ke WhatsApp ' . PortalOtpService::maskPhone($customer->phone) . '.');
    }

    public function showVerifyForm(Request $request)
    {
        $customer = $this->pendingCustomer($request);
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        return view('customer-portal.verify', ['maskedPhone' => PortalOtpService::maskPhone($customer->phone)]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|max:10'], [], ['code' => 'kode']);
        $customer = $this->pendingCustomer($request);
        if (! $customer) {
            return redirect()->route('customer.login')->with('error', 'Sesi masuk berakhir. Silakan ulangi.');
        }

        try {
            $this->otp->verify($customer, $request->code);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $remember = (bool) $request->session()->get(self::SESSION_KEY . '.remember');
        $request->session()->forget(self::SESSION_KEY);

        return $this->signIn($request, $customer, $remember);
    }

    public function resend(Request $request)
    {
        $customer = $this->pendingCustomer($request);
        if (! $customer) {
            return redirect()->route('customer.login');
        }
        try {
            $this->otp->send($customer);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Kode baru dikirim ke WhatsApp ' . PortalOtpService::maskPhone($customer->phone) . '.');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    private function signIn(Request $request, Customer $customer, ?bool $remember = null)
    {
        Auth::guard('customer')->login($customer, $remember ?? $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard')->with('success', 'Selamat datang di Portal Pelanggan!');
    }

    private function pendingCustomer(Request $request): ?Customer
    {
        $id = $request->session()->get(self::SESSION_KEY . '.id');

        return $id ? Customer::whereNotIn('status', PortalOtpService::BLOCKED_STATUSES)->find($id) : null;
    }

    private function findCustomer(string $code, string $phone): ?Customer
    {
        return PortalOtpService::findCustomer($code, $phone);
    }
}
