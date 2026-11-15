@php
    $companyName = get_setting('company_name', config('app.name'));
    $invoiceSnap = $report->snapshot['invoice'] ?? null;
    $invoice = $invoiceSnap ? \App\Models\Invoice::find($invoiceSnap['id'] ?? null) : null;
    $grace = $report->customer?->activation_grace_expires_at;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Berita Acara {{ $report->number }} · {{ $companyName }}</title>
    <style>
        :root { color-scheme: light dark; --bg:#f8fafc; --card:#fff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0;
                --primary:#4f46e5; --primary-h:#4338ca; --ok:#047857; --ok-bg:#ecfdf5; --warn:#b45309; --warn-bg:#fffbeb; --err:#be123c; --err-bg:#fff1f2; }
        @media (prefers-color-scheme: dark) { :root { --bg:#020617; --card:#0f172a; --text:#f1f5f9; --muted:#94a3b8; --border:#1e293b;
                --ok:#34d399; --ok-bg:rgba(52,211,153,.08); --warn:#fbbf24; --warn-bg:rgba(251,191,36,.08); --err:#fb7185; --err-bg:rgba(251,113,133,.08); } }
        * { box-sizing: border-box; }
        body { margin:0; padding:24px 16px 48px; background:var(--bg); color:var(--text);
               font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing:antialiased; }
        .wrap { max-width:760px; margin:0 auto; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:24px; margin-bottom:16px; }
        .note { border-radius:10px; padding:12px 14px; font-size:14px; margin-bottom:16px; }
        .note.ok { color:var(--ok); background:var(--ok-bg); }
        .note.warn { color:var(--warn); background:var(--warn-bg); }
        .note.err { color:var(--err); background:var(--err-bg); }
        .note a { color:inherit; font-weight:600; }
        h3 { margin:0 0 12px; font-size:16px; }
        label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
        input[type=text] { width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text); font:inherit; }
        .check { display:flex; gap:10px; align-items:flex-start; font-weight:400; font-size:14px; margin:14px 0; }
        .check input { margin-top:3px; }
        .btn { display:block; width:100%; padding:12px; border:0; border-radius:10px; background:var(--primary); color:#fff; font:inherit; font-weight:600; cursor:pointer; }
        .btn:hover { background:var(--primary-h); }
        .field { margin-bottom:14px; }
        @media (max-width: 480px) { body { padding: 16px 12px 40px; } .card { padding: 16px; } }
        .err-list { margin:0; padding-left:18px; }
    </style>
    @include('installation-reports._styles')
</head>
<body>
<div class="wrap">
    @if(session('success'))<div class="note ok no-print">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="note err no-print">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="note err no-print"><ul class="err-list">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if($invoice && $invoice->status !== 'paid')
        <div class="note warn no-print">
            Biaya instalasi <strong>Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</strong> belum dibayar.
            @if($grace) Mohon lunasi sebelum <strong>{{ $grace->locale('id')->translatedFormat('d M Y H:i') }}</strong> agar layanan tidak diisolir. @endif
            <a href="{{ route('portal.pay', $invoice->invoice_number) }}">Bayar sekarang</a>
        </div>
    @endif

    <div class="card ba-sheet">@include('installation-reports._document')</div>
    <p class="no-print" style="text-align:right;margin:-6px 0 16px"><button type="button" onclick="window.print()" style="font:inherit;font-size:13px;padding:6px 12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);cursor:pointer">Cetak / simpan PDF</button></p>

    @if(! $report->customer_signature)
        <form method="POST" action="{{ route('installation-report.public.sign', $report->sign_token) }}" class="card no-print">
            @csrf
            <h3>Tanda tangan pelanggan</h3>
            <div class="field">
                <label for="signer_name">Nama lengkap</label>
                <input type="text" id="signer_name" name="signer_name" maxlength="120" required value="{{ old('signer_name', $report->snapshot['customer']['name'] ?? '') }}">
            </div>
            @include('installation-reports._pad', ['role' => 'customer'])
            <label class="check"><input type="checkbox" name="agree" value="1" required> Saya telah memeriksa isi Berita Acara ini dan menyetujuinya.</label>
            <button class="btn">Tanda tangani</button>
        </form>
    @else
        <div class="note ok no-print">Berita Acara ini sudah Anda tanda tangani. Simpan tautan ini sebagai bukti.</div>
    @endif
</div>
</body>
</html>
