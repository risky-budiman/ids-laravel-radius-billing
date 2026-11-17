<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/** Verifikasi dua langkah (TOTP) untuk staf. */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    private function pending(Request $request): ?User
    {
        $p = $request->session()->get('two_factor_login');
        if (! is_array($p) || ($p['expires'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget('two_factor_login');

            return null;
        }

        return User::find($p['user_id']);
    }

    // ------------------------------------------------------------------ login

    public function challenge(Request $request)
    {
        if (! $this->pending($request)) {
            return redirect()->route('login')->withErrors(['login' => 'Sesi verifikasi berakhir. Masuk lagi dengan email & password.']);
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $user = $this->pending($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['login' => 'Sesi verifikasi berakhir. Masuk lagi dengan email & password.']);
        }
        $key = 'two-factor:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.']);
        }
        $code = (string) $request->validate(['code' => 'required|string|max:20'])['code'];
        $via = $this->twoFactor->verify($user, $code);
        if (! $via) {
            RateLimiter::hit($key, 60);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'two_factor_failed', 'description' => "Kode verifikasi dua langkah salah untuk {$user->name}.",
                'ip_address' => $request->ip(), 'user_agent' => (string) $request->userAgent()]);

            return back()->withErrors(['code' => 'Kode salah atau sudah dipakai. Gunakan kode terbaru dari aplikasi authenticator, atau kode cadangan.']);
        }
        RateLimiter::clear($key);
        $remember = (bool) ($request->session()->get('two_factor_login')['remember'] ?? false);
        $request->session()->forget('two_factor_login');
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($via === 'recovery') {
            $left = count((array) $user->fresh()->two_factor_recovery_codes);
            session()->flash('warning', "Anda masuk dengan kode cadangan. Sisa {$left} kode — buat kode cadangan baru di Profil bila perlu.");
        }

        return $user->isMitra() ? redirect()->route('partners.show', $user) : redirect()->intended(route('dashboard', absolute: false));
    }

    // ------------------------------------------------------------------ profil

    /** Mulai aktivasi: secret baru disimpan sementara di sesi sampai dikonfirmasi dengan kode. */
    public function enable(Request $request)
    {
        abort_if($request->user()->hasTwoFactor(), 422, 'Verifikasi dua langkah sudah aktif.');
        $request->session()->put('two_factor_setup', Totp::generateSecret());

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah');
    }

    public function confirm(Request $request)
    {
        $secret = $request->session()->get('two_factor_setup');
        if (! $secret) {
            return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')->withErrors(['two_factor_code' => 'Mulai aktivasi lagi.']);
        }
        $code = (string) $request->validate(['two_factor_code' => 'required|string|max:10'])['two_factor_code'];
        $codes = $this->twoFactor->confirm($request->user(), $secret, $code);
        if (! $codes) {
            return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')->withErrors(['two_factor_code' => 'Kode salah. Pastikan jam HP tepat, lalu masukkan kode terbaru.']);
        }
        $request->session()->forget('two_factor_setup');

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')->with('two_factor_recovery', $codes)
            ->with('success', 'Verifikasi dua langkah aktif. Simpan kode cadangan di bawah di tempat aman.');
    }

    public function cancelSetup(Request $request)
    {
        $request->session()->forget('two_factor_setup');

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah');
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        if (TwoFactorService::requiredFor($user)) {
            return back()->withErrors(['two_factor_password' => 'Verifikasi dua langkah wajib untuk role Anda dan tidak bisa dinonaktifkan.']);
        }
        $data = $request->validate(['two_factor_password' => 'required|string', 'two_factor_code' => 'required|string|max:20']);
        if (! Hash::check($data['two_factor_password'], $user->password)) {
            return back()->withErrors(['two_factor_password' => 'Password salah.']);
        }
        if (! $this->twoFactor->verify($user, $data['two_factor_code'])) {
            return back()->withErrors(['two_factor_code' => 'Kode salah atau sudah dipakai.']);
        }
        $this->twoFactor->disable($user, 'oleh pemilik akun');

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')->with('success', 'Verifikasi dua langkah dinonaktifkan.');
    }

    public function regenerateRecovery(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactor(), 422);
        if (! Hash::check((string) $request->validate(['two_factor_password' => 'required|string'])['two_factor_password'], $user->password)) {
            return back()->withErrors(['two_factor_password' => 'Password salah.']);
        }
        $codes = $this->twoFactor->newRecoveryCodes($user);
        ActivityLog::log('two_factor_recovery_regenerated', "Kode cadangan verifikasi dua langkah {$user->name} dibuat ulang.", $user);

        return redirect()->to(route('profile.edit') . '#verifikasi-dua-langkah')->with('two_factor_recovery', $codes)
            ->with('success', 'Kode cadangan baru dibuat; kode lama tidak berlaku lagi.');
    }

    // ------------------------------------------------------------------ administrator

    /** Reset 2FA staf yang kehilangan HP & kode cadangan. */
    public function reset(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa mereset verifikasi akun sendiri dari sini.');
        $this->twoFactor->disable($user, 'direset oleh ' . $request->user()->name);

        return back()->with('success', "Verifikasi dua langkah {$user->name} direset. Bila wajib untuk rolenya, ia diminta mengaktifkan lagi saat masuk.");
    }

    public function required(Request $request)
    {
        $roles = $request->validate(['roles' => 'array', 'roles.*' => [Rule::in(TwoFactorService::STAFF_ROLES)]])['roles'] ?? [];
        TwoFactorService::setRequiredRoles($roles);
        ActivityLog::log('two_factor_required_updated', 'Verifikasi dua langkah wajib untuk: ' . ($roles ? implode(', ', $roles) : 'tidak ada') . '.');

        return back()->with('success', $roles ? 'Verifikasi dua langkah wajib untuk role: ' . implode(', ', array_map(fn ($r) => User::STAFF_ROLES[$r] ?? $r, $roles)) . '.' : 'Verifikasi dua langkah tidak diwajibkan untuk role mana pun.');
    }
}
