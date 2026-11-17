<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return string|null 'two_factor' bila password benar tetapi kode 2FA masih diperlukan
     *
     * @throws ValidationException
     */
    public function authenticate(): ?string
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('login');
        $password = $this->input('password');
        $user = null;

        // 1. Try to find by Email (Standard User/Admin)
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = \App\Models\User::where('email', $login)->first();
        }

        // 2. Try to find by Customer ID or Username
        if (!$user) {
            $customer = \App\Models\Customer::where('customer_code', $login)
                ->orWhere('username', $login)
                ->first();
                
            if ($customer) {
                // Check if user account already linked
                if ($customer->user_id) {
                    $user = $customer->user;
                } else {
                    // ZERO-TOUCH LOGIC: Check password against Customer PHONE Number
                    if ($customer->phone && $customer->phone === $password) {
                        // Password correct! Auto-create the portal user
                        $user = \App\Models\User::create([
                            'name' => $customer->name,
                            'email' => $customer->email ?? ($customer->username . '@radius.local'),
                            'password' => \Illuminate\Support\Facades\Hash::make($password),
                            'role' => \App\Models\User::ROLE_CUSTOMER,
                            'is_active' => true,
                        ]);
                        
                        $customer->update(['user_id' => $user->id]);
                    }
                }
            }
        }

        // Use the found user's email for attempt
        $credentials = [
            'email' => $user ? $user->email : $login,
            'password' => $password,
        ];

        // Staf dengan verifikasi dua langkah: cek password TANPA login, lalu minta kode 6 digit
        if ($user && $user->role !== \App\Models\User::ROLE_CUSTOMER && $user->hasTwoFactor()) {
            if (! Auth::validate($credentials)) {
                RateLimiter::hit($this->throttleKey());
                RateLimiter::hit($this->accountThrottleKey(), self::ACCOUNT_DECAY_SECONDS);

                throw ValidationException::withMessages(['login' => trans('auth.failed')]);
            }
            RateLimiter::clear($this->throttleKey());
            $this->session()->put('two_factor_login', ['user_id' => $user->id, 'remember' => $this->boolean('remember'),
                'expires' => now()->addMinutes(10)->getTimestamp()]);

            return 'two_factor';
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountThrottleKey(), self::ACCOUNT_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return null;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $perIp = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        // Batas per AKUN, tidak bergantung IP: menebak password dengan berganti-ganti IP
        // (atau memalsukan X-Forwarded-For) tetap terhenti.
        $perAccount = RateLimiter::tooManyAttempts($this->accountThrottleKey(), self::ACCOUNT_MAX_ATTEMPTS);

        if (! $perIp && ! $perAccount) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $perIp ? RateLimiter::availableIn($this->throttleKey()) : 0,
            $perAccount ? RateLimiter::availableIn($this->accountThrottleKey()) : 0,
        );

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }

    /** Maks. gagal login per akun (semua IP) dalam ACCOUNT_DECAY_SECONDS. */
    public const ACCOUNT_MAX_ATTEMPTS = 20;
    public const ACCOUNT_DECAY_SECONDS = 900;

    public function accountThrottleKey(): string
    {
        return 'login-account|' . Str::transliterate(Str::lower($this->string('login')));
    }
}
