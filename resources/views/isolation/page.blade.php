@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $total = $invoices->sum('amount');
    $waNumber = $companyPhone ? preg_replace('/^0/', '62', preg_replace('/\D+/', '', $companyPhone)) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Layanan Diisolir · {{ $companyName }}</title>
    <style>
        :root { color-scheme: light dark; --bg:#f8fafc; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0;
                --primary:#4f46e5; --primary-h:#4338ca; --warn:#b45309; --warn-bg:#fffbeb; --warn-bd:#fcd34d; }
        @media (prefers-color-scheme: dark) { :root { --bg:#020617; --card:#0f172a; --text:#f1f5f9; --muted:#94a3b8; --border:#1e293b;
                --warn:#fbbf24; --warn-bg:rgba(251,191,36,.08); --warn-bd:rgba(251,191,36,.35); } }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; padding:32px 16px; background:var(--bg); color:var(--text);
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing:antialiased; }
        .wrap { max-width:480px; margin:0 auto; }
        .brand { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:24px; font-weight:600; }
        .brand img { height:36px; width:auto; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:24px; }
        .badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600;
                 color:var(--warn); background:var(--warn-bg); border:1px solid var(--warn-bd); }
        h1 { margin:14px 0 6px; font-size:22px; letter-spacing:-.015em; }
        p { margin:0; color:var(--muted); font-size:15px; line-height:1.6; }
        .who { margin-top:18px; padding:12px 14px; border-radius:10px; background:var(--bg); font-size:14px; }
        .who b { color:var(--text); }
        .inv { margin-top:16px; border-top:1px solid var(--border); }
        .inv-row { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid var(--border); }
        .inv-row small { display:block; color:var(--muted); font-size:12px; margin-top:2px; }
        .amt { font-weight:600; font-variant-numeric: tabular-nums; white-space:nowrap; }
        .total { display:flex; justify-content:space-between; padding:14px 0 4px; font-weight:600; }
        .btn { display:flex; align-items:center; justify-content:center; height:44px; margin-top:12px; border-radius:10px; font:600 15px/1 inherit;
               text-decoration:none; background:var(--primary); color:#fff; }
        .btn:hover { background:var(--primary-h); }
        .btn-sm { display:inline-flex; height:34px; margin:0; padding:0 12px; font-size:13px; }
        .btn-ghost { background:transparent; color:var(--text); border:1px solid var(--border); }
        .btn-ghost:hover { background:var(--bg); }
        .foot { margin-top:20px; text-align:center; font-size:12px; color:var(--muted); line-height:1.6; }
    </style>
</head>
<body>
<main class="wrap">
    <div class="brand">
        @if($companyLogo)
            <img src="{{ asset('storage/' . $companyLogo) }}" alt="">
        @endif
        <span>{{ $companyName }}</span>
    </div>

    <section class="card">
        <span class="badge">● Layanan sedang diisolir</span>

        @if($customer)
            <h1>Halo, {{ $customer->name }}</h1>
            <p>Akses internet Anda sementara dibatasi karena ada tagihan yang belum dibayar. Setelah pembayaran diterima, layanan aktif kembali secara otomatis.</p>

            <div class="who">ID Pelanggan: <b>{{ $customer->customer_code ?: $customer->username }}</b></div>

            @if($invoices->isNotEmpty())
                <div class="inv">
                    @foreach($invoices as $inv)
                        <div class="inv-row">
                            <div>
                                <div>{{ $inv->invoice_number }}</div>
                                <small>Jatuh tempo {{ $inv->due_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</small>
                            </div>
                            <div style="text-align:right">
                                <div class="amt">{{ $rp($inv->amount) }}</div>
                                @if($invoices->count() > 1)
                                    <a href="{{ route('portal.pay', $inv->invoice_number) }}" class="btn btn-sm btn-ghost" style="margin-top:6px">Bayar</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($invoices->count() > 1)
                    <div class="total"><span>Total tagihan</span><span class="amt">{{ $rp($total) }}</span></div>
                    <p style="font-size:13px">Lunasi semua tagihan agar layanan aktif kembali.</p>
                @else
                    <a href="{{ route('portal.pay', $invoices->first()->invoice_number) }}" class="btn">Bayar {{ $rp($total) }}</a>
                @endif
            @else
                <p style="margin-top:16px">Tidak ada tagihan terbuka yang tercatat. Silakan hubungi kami untuk membuka kembali layanan Anda.</p>
            @endif
        @else
            <h1>Akses internet dibatasi</h1>
            <p>Layanan internet untuk perangkat ini sedang diisolir. Silakan masuk ke portal pelanggan untuk melihat dan membayar tagihan Anda.</p>
            @if(Route::has('customer.login'))
                <a href="{{ route('customer.login') }}" class="btn">Masuk ke portal pelanggan</a>
            @endif
        @endif

        @if($waNumber)
            <a href="https://wa.me/{{ $waNumber }}" class="btn btn-ghost">Hubungi kami via WhatsApp</a>
        @endif
    </section>

    <p class="foot">
        Sudah membayar? Layanan aktif otomatis dalam beberapa menit.<br>
        Bila belum tersambung, matikan lalu nyalakan kembali modem Anda.
    </p>
</main>
</body>
</html>
