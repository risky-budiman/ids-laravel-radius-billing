<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if ($request->authenticate() === 'two_factor') {
            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if (Auth::user()->isCustomer()) {
            return redirect()->intended(route('customer.dashboard', absolute: false));
        }

        // Mitra tidak punya dasbor staf: langsung ke halaman mitranya
        if (Auth::user()->isMitra()) {
            return redirect()->route('partners.show', Auth::user());
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/admin');
    }
}
