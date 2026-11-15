@php
    $s = $report->snapshot;
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $date = $report->created_at->locale('id')->translatedFormat('l, d F Y');
    $companyName = get_setting('company_name', config('app.name'));
    $companyAddress = get_setting('company_address');
    $companyLogo = get_setting('company_logo');
@endphp
<article class="ba-doc">
    <header class="ba-head">
        @if($companyLogo)<img src="{{ asset('storage/' . $companyLogo) }}" alt="" class="ba-logo">@endif
        <div>
            <div class="ba-company">{{ $companyName }}</div>
            @if($companyAddress)<div class="ba-muted">{{ $companyAddress }}</div>@endif
        </div>
    </header>

    <h1 class="ba-title">BERITA ACARA INSTALASI</h1>
    <p class="ba-center ba-muted">Nomor: <strong class="ba-mono">{{ $report->number }}</strong></p>

    <p>Pada hari {{ $date }} telah dilakukan pemasangan layanan internet dengan rincian berikut:</p>

    <h2>Data Pelanggan</h2>
    <table class="ba-kv">
        <tr><th>Nama</th><td>{{ $s['customer']['name'] ?? '—' }}</td></tr>
        <tr><th>ID Pelanggan</th><td class="ba-mono">{{ $s['customer']['customer_code'] ?? '—' }}</td></tr>
        <tr><th>No. HP</th><td>{{ $s['customer']['phone'] ?? '—' }}</td></tr>
        <tr><th>Alamat Pemasangan</th><td>{{ $s['customer']['address'] ?? '—' }}</td></tr>
        @if(! empty($s['location']['latitude']))
            <tr><th>Koordinat</th><td class="ba-mono">{{ $s['location']['latitude'] }}, {{ $s['location']['longitude'] }}</td></tr>
        @endif
    </table>

    <h2>Layanan</h2>
    <table class="ba-kv">
        <tr><th>Paket</th><td>{{ $s['package']['name'] ?? '—' }} @if(! empty($s['package']['speed']))({{ $s['package']['speed'] }})@endif</td></tr>
        <tr><th>Biaya Bulanan</th><td>{{ $rp($s['package']['price'] ?? 0) }}</td></tr>
        <tr><th>Username PPPoE</th><td class="ba-mono">{{ $s['customer']['username'] ?? '—' }}</td></tr>
        @if(! empty($s['network']['onu_sn']))<tr><th>SN ONU</th><td class="ba-mono">{{ $s['network']['onu_sn'] }}</td></tr>@endif
        @if(! empty($s['location']['odp_port']))<tr><th>Port ODP</th><td>{{ $s['location']['odp_port'] }}</td></tr>@endif
        @if(! empty($s['network']['static_ip']))<tr><th>IP Statis</th><td class="ba-mono">{{ $s['network']['static_ip'] }}</td></tr>@endif
        <tr><th>Tanggal Aktif</th><td>{{ ! empty($s['activated_at']) ? \Carbon\Carbon::parse($s['activated_at'])->locale('id')->translatedFormat('d F Y H:i') : '—' }}</td></tr>
    </table>

    <h2>Perangkat Terpasang (milik perusahaan)</h2>
    @if(empty($s['devices']))
        <p class="ba-muted">Tidak ada perangkat bernomor seri yang tercatat.</p>
    @else
        <div class="ba-scroll"><table class="ba-grid">
            <thead><tr><th>Perangkat</th><th>Nomor Seri</th><th>MAC</th><th>Kondisi</th></tr></thead>
            <tbody>
                @foreach($s['devices'] as $d)
                    <tr><td>{{ $d['item'] }}</td><td class="ba-mono">{{ $d['serial_number'] }}</td><td class="ba-mono">{{ $d['mac_address'] ?: '—' }}</td>
                        <td>{{ ['new' => 'Baru', 'used' => 'Bekas', 'refurbished' => 'Rekondisi'][$d['condition']] ?? $d['condition'] }}</td></tr>
                @endforeach
            </tbody>
        </table></div>
    @endif

    @if(! empty($s['materials']))
        <h2>Material Terpakai</h2>
        <table class="ba-grid">
            <thead><tr><th>Material</th><th class="ba-right">Jumlah</th></tr></thead>
            <tbody>
                @foreach($s['materials'] as $m)
                    <tr><td>{{ $m['item'] }}</td><td class="ba-right">{{ rtrim(rtrim(number_format($m['quantity'], 2, ',', '.'), '0'), ',') }} {{ $m['unit'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Biaya Instalasi</h2>
    <table class="ba-kv">
        <tr><th>Biaya</th><td>{{ ($s['installation_fee'] ?? 0) > 0 ? $rp($s['installation_fee']) : 'Gratis' }}</td></tr>
        @if(! empty($s['invoice']))
            <tr><th>Invoice</th><td class="ba-mono">{{ $s['invoice']['number'] }} — {{ $s['invoice']['status'] === 'paid' ? 'Lunas' : 'Belum dibayar' }}</td></tr>
        @endif
    </table>

    @if($report->terms)
        <h2>Ketentuan</h2>
        <div class="ba-terms">{!! nl2br(e($report->terms)) !!}</div>
    @endif

    <p>Dengan ditandatanganinya Berita Acara ini, kedua pihak menyatakan pemasangan telah selesai dan layanan berfungsi dengan baik.</p>

    <div class="ba-signs">
        @foreach(['customer' => 'Pelanggan', 'technician' => 'Teknisi'] as $role => $label)
            <div class="ba-sign">
                <div class="ba-muted">{{ $label }}</div>
                <div class="ba-sign-box">
                    @if($report->{$role . '_signature'})
                        <img src="{{ asset('storage/' . $report->{$role . '_signature'}) }}" alt="Tanda tangan {{ $label }}">
                    @else
                        <span class="ba-muted">Belum ditandatangani</span>
                    @endif
                </div>
                <div class="ba-sign-name">{{ $role === 'customer' ? ($report->customer_signer_name ?? ($s['customer']['name'] ?? '')) : ($s['technician'] ?? '') }}</div>
                @if($report->{$role . '_signed_at'})
                    <div class="ba-muted ba-small">{{ $report->{$role . '_signed_at'}->locale('id')->translatedFormat('d M Y H:i') }}</div>
                @endif
            </div>
        @endforeach
    </div>
</article>
