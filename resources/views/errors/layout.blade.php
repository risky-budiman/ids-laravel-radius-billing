@php
    // Halaman error harus selalu bisa tampil: hindari Vite/DB yang mungkin sedang bermasalah.
    try { $companyName = get_setting('company_name', config('app.name', 'Radius ISP')); } catch (\Throwable $e) { $companyName = config('app.name', 'Radius ISP'); }
    $isCustomer = auth('customer')->check() || request()->is('client*', 'portal*', 'pay/*');
    $homeUrl = $isCustomer
        ? (Route::has('customer.dashboard') && auth('customer')->check() ? route('customer.dashboard') : (Route::has('customer.login') ? route('customer.login') : url('/')))
        : (auth('web')->check() && Route::has('dashboard') ? route('dashboard') : (Route::has('login') ? route('login') : url('/')));
    $homeLabel = auth('web')->check() || auth('customer')->check() ? 'Ke beranda' : 'Ke halaman masuk';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title') · {{ $companyName }}</title>
    <style>
        :root { color-scheme: light dark; --bg:#f8fafc; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0; --primary:#4f46e5; --primary-h:#4338ca; --tint:#eef2ff; }
        @media (prefers-color-scheme: dark) { :root { --bg:#020617; --card:#0f172a; --text:#f1f5f9; --muted:#94a3b8; --border:#1e293b; --tint:rgba(99,102,241,.15); } }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:var(--bg); color:var(--text); -webkit-font-smoothing:antialiased; }
        .box { width:100%; max-width:440px; text-align:center; }
        .icon { width:56px; height:56px; margin:0 auto 20px; border-radius:14px; display:flex; align-items:center; justify-content:center; background:var(--tint); color:var(--primary); }
        .code { font-size:13px; font-weight:600; color:var(--primary); letter-spacing:.04em; }
        h1 { margin:6px 0 8px; font-size:24px; font-weight:600; letter-spacing:-.015em; }
        p { margin:0; color:var(--muted); font-size:15px; line-height:1.6; }
        .actions { margin-top:28px; display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; height:40px; padding:0 16px; border-radius:8px; font:500 14px/1 inherit; text-decoration:none; cursor:pointer; border:1px solid transparent; }
        .btn-primary { background:var(--primary); color:#fff; } .btn-primary:hover { background:var(--primary-h); }
        .btn-secondary { background:var(--card); color:var(--text); border-color:var(--border); }
        .foot { margin-top:40px; font-size:12px; color:var(--muted); }
    </style>
</head>
<body>
    <main class="box">
        <div class="icon" aria-hidden="true">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="@yield('icon', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z')"/></svg>
        </div>
        <div class="code">Error @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="{{ $homeUrl }}" class="btn btn-primary">{{ $homeLabel }}</a>
            <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ $homeUrl }}')" class="btn btn-secondary">Kembali</button>
        </div>
        <div class="foot">{{ $companyName }}</div>
    </main>
</body>
</html>
