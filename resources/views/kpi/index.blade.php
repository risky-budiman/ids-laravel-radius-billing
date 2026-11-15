@php
    $rp = fn ($v) => $v === null ? '—' : rupiah($v, 0);
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.') . '%';
    $c = $current;
    $labels = array_map(fn ($r) => $r['month']->locale('id')->translatedFormat('M y'), array_values($rows));
    $mrrSeries = array_column(array_values($rows), 'mrr');
    $newSeries = array_column(array_values($rows), 'new');
    $stopSeries = array_map(fn ($v) => -$v, array_column(array_values($rows), 'stopped'));
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-report-header title="KPI Bisnis" :subtitle="$months . ' bulan terakhir · per ' . $asOf->locale('id')->translatedFormat('d F Y')">
            <x-slot name="filters">
                <form method="GET" class="flex items-end gap-2">
                    <div>
                        <label for="months" class="label !mb-1 text-xs">Rentang</label>
                        <select id="months" name="months" class="input !h-9 !w-auto" onchange="this.form.submit()">
                            @foreach([6, 12, 24] as $opt)
                                <option value="{{ $opt }}" @selected($months === $opt)>{{ $opt }} bulan</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('kpi.export', request()->query()) }}" class="btn-secondary">
                    <x-icon name="arrow-down" class="w-4 h-4" /> Ekspor Excel
                </a>
            </x-slot>
        </x-report-header>
    </x-slot>

    <div class="space-y-6">
        <div class="no-print grid grid-cols-2 lg:grid-cols-5 gap-4">
            <x-stat-card label="MRR saat ini" :value="$rp($c['mrr'])" icon="trending-up" tone="primary"
                         :hint="$mrrChange === null ? 'Pendapatan berulang bulanan' : (($mrrChange >= 0 ? '+' : '') . number_format($mrrChange, 1, ',', '.') . '% dari bulan lalu')" />
            <x-stat-card label="ARPU" :value="$rp($c['arpu'])" icon="cash" tone="info" hint="Rata-rata per pelanggan / bulan" />
            <x-stat-card label="Pelanggan berlangganan" :value="number_format($c['end_count'], 0, ',', '.')" icon="users" tone="success"
                         hint="Termasuk yang terisolir" />
            <x-stat-card label="Churn bulanan (rata-rata)" :value="$pct($avgChurn)" icon="trending-down" tone="warning"
                         :hint="'Bulan ini: ' . $pct($c['churn_pct'])" />
            <x-stat-card label="Baru vs berhenti" :value="'+' . $totalNew . ' / ' . ($totalStopped ? '−' . $totalStopped : '0')" icon="user-plus" tone="gray"
                         :hint="'Bersih ' . (($totalNew - $totalStopped) >= 0 ? '+' : '') . ($totalNew - $totalStopped) . ' dalam ' . $months . ' bulan'" />
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <section class="card">
                <div class="card-header block">
                    <h3 class="card-title">MRR per bulan</h3>
                    <p class="mt-0.5 text-xs text-surface-500 dark:text-surface-400">Harga paket bulanan pelanggan berlangganan di akhir bulan, setelah diskon, tanpa PPN.</p>
                </div>
                <div class="card-body"><div class="h-64"><canvas id="mrrChart" role="img" aria-label="Grafik MRR per bulan"></canvas></div></div>
            </section>
            <section class="card">
                <div class="card-header block">
                    <h3 class="card-title">Pelanggan baru vs berhenti</h3>
                    <p class="mt-0.5 text-xs text-surface-500 dark:text-surface-400">Batang ke atas = mulai berlangganan; ke bawah = dicabut/batal setelah aktif.</p>
                </div>
                <div class="card-body"><div class="h-64"><canvas id="flowChart" role="img" aria-label="Grafik pelanggan baru dan berhenti per bulan"></canvas></div></div>
            </section>
        </div>

        <section class="card overflow-hidden">
            <div class="card-header"><h3 class="card-title">Rincian per bulan</h3></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-right">Awal</th>
                            <th class="text-right">Baru</th>
                            <th class="text-right">Berhenti</th>
                            <th class="text-right">Akhir</th>
                            <th class="text-right">Churn</th>
                            <th class="text-right">MRR</th>
                            <th class="text-right">ARPU</th>
                            <th class="text-right">Ditagih</th>
                            <th class="text-right">Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_reverse($rows) as $r)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-surface-900 dark:text-white">
                                    {{ $r['month']->locale('id')->translatedFormat('F Y') }}
                                    @if($r['is_current'])<span class="badge-gray ml-1">berjalan</span>@endif
                                </td>
                                <td class="amount">{{ $r['start_count'] }}</td>
                                <td class="amount">{{ $r['new'] ? '+' . $r['new'] : '0' }}</td>
                                <td class="amount">{{ $r['stopped'] ? '−' . $r['stopped'] : '0' }}</td>
                                <td class="amount font-medium text-surface-900 dark:text-white">{{ $r['end_count'] }}</td>
                                <td class="amount">{{ $pct($r['churn_pct']) }}</td>
                                <td class="amount font-medium text-surface-900 dark:text-white">{{ rupiah($r['mrr'], 0, true, false) }}</td>
                                <td class="amount">{{ $r['arpu'] === null ? '—' : rupiah($r['arpu'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($r['billed'], 0, true, false) }}</td>
                                <td class="amount">{{ rupiah($r['collected'], 0, true, false) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-xs text-surface-500 dark:text-surface-400">
            <strong>MRR</strong> memakai harga paket saat ini (riwayat perubahan harga tidak tersimpan). <strong>Churn</strong> = pelanggan berhenti dalam bulan ÷ pelanggan berlangganan di awal bulan; rata-rata dihitung dari bulan yang sudah selesai.
            <strong>Ditagih</strong> = invoice langganan menurut tanggal terbit, <strong>Diterima</strong> = menurut tanggal lunas (tanpa PPN, booster & instalasi).
            Pelanggan lama tanpa tanggal aktivasi dihitung sejak tanggal didaftarkan.
        </p>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;
            const dark = document.documentElement.classList.contains('dark');
            // Palet tervalidasi (slot 1 biru, slot 2 oranye) — langkah terpisah untuk mode gelap
            const c1 = dark ? '#3987e5' : '#2a78d6';
            const c2 = dark ? '#d95926' : '#eb6834';
            const grid = dark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
            const ink = dark ? '#94a3b8' : '#64748b';
            const surface = dark ? '#0f172a' : '#ffffff';
            const labels = @json($labels);
            const rp = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
            const short = (v) => Math.abs(v) >= 1e6 ? (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt'
                : Math.abs(v) >= 1e3 ? (v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb' : v;
            Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
            const tooltip = {
                backgroundColor: surface, titleColor: dark ? '#f8fafc' : '#0f172a', bodyColor: ink,
                borderColor: grid, borderWidth: 1, padding: 10, usePointStyle: true, boxPadding: 4,
            };
            const axes = (yTick) => ({
                x: { grid: { display: false }, border: { display: false }, ticks: { color: ink, font: { size: 11 }, maxRotation: 0, autoSkipPadding: 10 } },
                y: { grid: { color: grid }, border: { display: false }, ticks: { color: ink, font: { size: 11 }, callback: yTick } },
            });

            const mrr = document.getElementById('mrrChart');
            if (mrr) {
                const g = mrr.getContext('2d').createLinearGradient(0, 0, 0, 256);
                g.addColorStop(0, dark ? 'rgba(57,135,229,0.20)' : 'rgba(42,120,214,0.16)'); g.addColorStop(1, 'rgba(42,120,214,0)');
                new Chart(mrr, {
                    type: 'line',
                    data: { labels, datasets: [{ label: 'MRR', data: @json($mrrSeries), borderColor: c1, backgroundColor: g, fill: true,
                        borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 5, pointHoverBorderWidth: 2, pointHoverBorderColor: surface, pointHoverBackgroundColor: c1 }] },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { display: false }, tooltip: { ...tooltip, callbacks: { label: (c) => ' MRR: ' + rp(c.parsed.y) } } },
                        scales: { ...axes((v) => short(v)), y: { ...axes((v) => short(v)).y, beginAtZero: true } },
                    },
                });
            }

            const flow = document.getElementById('flowChart');
            if (flow) {
                new Chart(flow, {
                    type: 'bar',
                    data: { labels, datasets: [
                        { label: 'Baru', data: @json($newSeries), backgroundColor: c1, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 22 },
                        { label: 'Berhenti', data: @json($stopSeries), backgroundColor: c2, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 22 },
                    ] },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { color: ink, usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, boxHeight: 8, padding: 14, font: { size: 12 } } },
                            tooltip: { ...tooltip, callbacks: { label: (c) => ` ${c.dataset.label}: ${Math.abs(c.parsed.y)} pelanggan` } },
                        },
                        scales: {
                            x: { ...axes().x, stacked: true },
                            y: { ...axes((v) => Number.isInteger(v) ? Math.abs(v) : '').y, stacked: true,
                                 ticks: { color: ink, font: { size: 11 }, precision: 0, callback: (v) => Math.abs(v) } },
                        },
                    },
                });
            }
        })();
    </script>
    @endpush
</x-app-layout>
