@php
    $u = auth()->user();
    $isAdmin = $u->isAdmin();              // administrator + admin
    $isAdministrator = $u->isAdministrator();
    $isKasir = $u->isKasir();
    $isTeknisi = $u->isTeknisi();
    $isSales = $u->isSales();

    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $bytes = fn ($b) => $b >= 1073741824
        ? number_format($b / 1073741824, 2, ',', '.') . ' GB'
        : number_format($b / 1048576, 1, ',', '.') . ' MB';

    $hour = now()->hour;
    $greeting = match (true) {
        $hour >= 5 && $hour < 11 => 'Selamat pagi',
        $hour >= 11 && $hour < 15 => 'Selamat siang',
        $hour >= 15 && $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = explode(' ', trim($u->name))[0];

    $onlineRatio = $totalSubscribers > 0 ? min(100, ($onlineNow / $totalSubscribers) * 100) : 0;
    $collectionRate = $mtdIssuedInvoiceAmount > 0 ? min(100, ($mtdPaidInvoiceAmount / $mtdIssuedInvoiceAmount) * 100) : 0;
    $now = now()->locale('id');
    $period = $now->translatedFormat('F Y');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2>{{ $greeting }}, {{ $firstName }}</h2>
                <p class="page-subtitle">Ringkasan jaringan dan penagihan · {{ $now->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden sm:inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-surface-200 dark:border-surface-700 text-sm text-surface-600 dark:text-surface-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="live-clock" class="font-mono tabular-nums">--:--:--</span>
                </span>
                <button type="button" onclick="window.location.reload()" class="btn-secondary">
                    <x-icon name="refresh" class="w-4 h-4" /> Muat ulang
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- ================= KPI utama ================= --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-stat-card label="Total Pelanggan" :value="number_format($totalSubscribers, 0, ',', '.')" icon="users" tone="primary"
                         :href="($isAdmin || $isTeknisi || $isSales) ? route('customers.index') : null">
                <x-slot name="footer">
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ number_format($activeUsers, 0, ',', '.') }}</span> akun aktif
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Sesi Online" :value="number_format($onlineNow, 0, ',', '.')" icon="signal" tone="success"
                         :href="($isAdmin || $isTeknisi) ? route('online-users.index') : null">
                <x-slot name="footer">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 rounded-full bg-surface-100 dark:bg-surface-800 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ number_format($onlineRatio, 1, '.', '') }}%"></div>
                        </div>
                        <span class="tabular-nums">{{ number_format($onlineRatio, 1, ',', '.') }}%</span>
                    </div>
                </x-slot>
            </x-stat-card>

            @if($isAdmin)
                <x-stat-card label="Kas Terkumpul" :value="$rp($cashRevenue)" icon="cash" tone="info"
                             :hint="'Invoice lunas · ' . $period" />

                <x-stat-card label="Piutang Belum Tertagih" :value="$rp($totalUnpaidInvoiceAmount)" icon="exclamation" tone="warning"
                             :href="route('invoices.index', ['status' => 'unpaid'])">
                    <x-slot name="footer">
                        <span class="font-medium text-amber-600 dark:text-amber-400">{{ number_format($unpaidInvoices, 0, ',', '.') }}</span> invoice belum dibayar
                    </x-slot>
                </x-stat-card>
            @else
                <x-stat-card label="Autentikasi Berhasil" :value="number_format($authAcceptToday, 0, ',', '.')" icon="check-circle" tone="info" hint="Hari ini" />
                <x-stat-card label="Autentikasi Gagal" :value="number_format($authRejectToday, 0, ',', '.')" icon="x-circle" tone="danger" hint="Hari ini" />
            @endif
        </section>

        {{-- ================= Keuangan (admin) ================= --}}
        @if($isAdmin)
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            {{-- Laba rugi --}}
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Laba Rugi Bulan Ini</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Berdasarkan jurnal akuntansi · {{ $period }}</p>
                    </div>
                    <a href="{{ route('accounting.reports.profit-loss') }}" class="btn-ghost btn-sm">Laporan lengkap <x-icon name="chevron-right" class="w-3.5 h-3.5" /></a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-surface-200 dark:divide-surface-800">
                    <div class="p-5">
                        <p class="flex items-center gap-1.5 text-sm text-surface-500 dark:text-surface-400">
                            <x-icon name="arrow-down" class="w-4 h-4 text-emerald-500" /> Pendapatan
                        </p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($revenue) }}</p>
                    </div>
                    <div class="p-5">
                        <p class="flex items-center gap-1.5 text-sm text-surface-500 dark:text-surface-400">
                            <x-icon name="arrow-up" class="w-4 h-4 text-rose-500" /> Pengeluaran
                        </p>
                        <p class="mt-1 text-xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($expense) }}</p>
                    </div>
                    <div class="p-5">
                        <p class="flex items-center justify-between gap-2 text-sm text-surface-500 dark:text-surface-400">
                            Laba Bersih
                            <span class="{{ $profit >= 0 ? 'badge-success' : 'badge-danger' }}">{{ $profit >= 0 ? 'Surplus' : 'Defisit' }}</span>
                        </p>
                        <p class="mt-1 text-xl font-semibold tabular-nums {{ $profit < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-surface-900 dark:text-white' }}">{{ $rp($profit) }}</p>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-surface-200 dark:border-surface-800 bg-surface-50/60 dark:bg-surface-800/30 rounded-b-xl flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-surface-600 dark:text-surface-300">
                        Total saldo kas & bank
                        <span class="ml-2 font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($totalCashBalance) }}</span>
                    </p>
                    <a href="{{ route('bank-accounts.index') }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">Lihat rekening</a>
                </div>
            </div>

            {{-- Penagihan --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Penagihan {{ $period }}</h3>
                    <a href="{{ route('invoices.index') }}" class="btn-ghost btn-sm">Invoice <x-icon name="chevron-right" class="w-3.5 h-3.5" /></a>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <div class="flex items-baseline justify-between">
                            <p class="text-sm text-surface-500 dark:text-surface-400">Tingkat koleksi</p>
                            <p class="text-sm font-semibold tabular-nums text-surface-900 dark:text-white">{{ number_format($collectionRate, 1, ',', '.') }}%</p>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-surface-100 dark:bg-surface-800 overflow-hidden">
                            <div class="h-full rounded-full bg-primary-600" style="width: {{ number_format($collectionRate, 1, '.', '') }}%"></div>
                        </div>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-surface-500 dark:text-surface-400">Invoice terbit</dt>
                            <dd class="font-medium tabular-nums text-surface-900 dark:text-white">{{ $rp($mtdIssuedInvoiceAmount) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-surface-500 dark:text-surface-400">Sudah dibayar</dt>
                            <dd class="font-medium tabular-nums text-emerald-600 dark:text-emerald-400">{{ $rp($mtdPaidInvoiceAmount) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-surface-500 dark:text-surface-400">Total piutang</dt>
                            <dd class="font-medium tabular-nums text-amber-600 dark:text-amber-400">{{ $rp($totalUnpaidInvoiceAmount) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        {{-- Pajak PPN --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Ringkasan Pajak PPN</h3>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Hutang, pajak masuk, dan setoran ke kas negara</p>
                </div>
                <a href="{{ route('accounting.taxes.index') }}" class="btn-secondary btn-sm">Kelola & setor PPN</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-surface-200 dark:divide-surface-800">
                <div class="p-5">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Hutang PPN (siap setor)</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums {{ $netTaxLiability > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-surface-900 dark:text-white' }}">{{ $rp($netTaxLiability) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-sm text-surface-500 dark:text-surface-400">PPN masuk bulan ini</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($mtdPaidTax) }}</p>
                </div>
                <div class="p-5">
                    <p class="text-sm text-surface-500 dark:text-surface-400">Total disetor ke negara</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-surface-900 dark:text-white">{{ $rp($totalTaxPaidToGovt) }}</p>
                </div>
            </div>
        </section>
        @endif

        {{-- ================= Jaringan & aksi ================= --}}
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            {{-- Grafik trafik --}}
            <div class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Trafik 24 Jam Terakhir</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Volume upload & download per jam dari sesi RADIUS</p>
                    </div>
                    @if($isAdmin)
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" @click.outside="open = false" class="btn-ghost btn-icon" aria-label="Aksi sesi">
                                <x-icon name="dots-vertical" class="w-5 h-5" />
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-20 mt-1 w-60 card shadow-elevated p-1">
                                <form action="{{ route('radius.clear-stale') }}" method="POST"
                                      onsubmit="return confirm('Bersihkan sesi menggantung (idle lebih dari 2 jam)?')">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-800">
                                        Bersihkan sesi menggantung
                                    </button>
                                </form>
                                <form action="{{ route('radius.disconnect-all') }}" method="POST"
                                      onsubmit="return confirm('Putuskan SEMUA sesi pelanggan yang sedang online? Semua pelanggan akan reconnect.')">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                        Putuskan semua sesi
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-surface-200 dark:bg-surface-800 border-b border-surface-200 dark:border-surface-800">
                    @foreach([
                        ['Upload aktif', $bytes($totalUpload), 'text-primary-600 dark:text-primary-400'],
                        ['Download aktif', $bytes($totalDownload), 'text-emerald-600 dark:text-emerald-400'],
                        ['Auth berhasil', number_format($authAcceptToday, 0, ',', '.'), 'text-surface-900 dark:text-white'],
                        ['Auth gagal', number_format($authRejectToday, 0, ',', '.'), $authRejectToday > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-surface-900 dark:text-white'],
                    ] as [$lbl, $val, $cls])
                        <div class="bg-white dark:bg-surface-900 px-5 py-3">
                            <p class="text-xs text-surface-500 dark:text-surface-400">{{ $lbl }}</p>
                            <p class="mt-0.5 text-base font-semibold tabular-nums {{ $cls }}">{{ $val }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="p-5">
                    <div class="relative h-72">
                        <canvas id="trafficChart" aria-label="Grafik trafik 24 jam" role="img"></canvas>
                    </div>
                </div>
            </div>

            {{-- Kolom kanan --}}
            <div class="space-y-4">
                {{-- Aksi cepat --}}
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Aksi Cepat</h3></div>
                    <div class="p-2">
                        @php
                            $actions = [];
                            if ($isAdmin || $isTeknisi || $isSales) $actions[] = ['Tambah pelanggan', 'Registrasi pelanggan baru', 'user-plus', route('customers.create'), 'primary'];
                            if ($isAdmin || $isKasir) $actions[] = ['Buat invoice', 'Invoice manual untuk pelanggan', 'document', route('invoices.create'), 'info'];
                            if ($isAdmin || $isTeknisi || $isSales) $actions[] = ['Tiket & gangguan', 'Lihat antrean tiket support', 'ticket', route('tickets.index'), 'warning'];
                            if ($isAdmin || $isTeknisi) $actions[] = ['Pengguna online', 'Pantau sesi PPPoE aktif', 'signal', route('online-users.index'), 'success'];
                            $toneCls = [
                                'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
                                'info' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                                'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                                'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                            ];
                        @endphp
                        @foreach($actions as [$title, $desc, $ico, $url, $tone])
                            <a href="{{ $url }}" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors">
                                <span class="flex items-center justify-center w-9 h-9 rounded-lg shrink-0 {{ $toneCls[$tone] }}"><x-icon :name="$ico" class="w-[18px] h-[18px]" /></span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium text-surface-900 dark:text-white">{{ $title }}</span>
                                    <span class="block text-xs text-surface-500 dark:text-surface-400 truncate">{{ $desc }}</span>
                                </span>
                                <x-icon name="chevron-right" class="w-4 h-4 text-surface-300 group-hover:text-surface-500 transition-colors" />
                            </a>
                        @endforeach

                        @if($isAdmin || $isKasir)
                            <form action="{{ route('invoices.generate-automated') }}" method="POST"
                                  onsubmit="return confirm('Generate invoice otomatis untuk periode berjalan sekarang?')">
                                @csrf
                                <button type="submit" class="group w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors text-left">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-lg shrink-0 bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-300"><x-icon name="receipt" class="w-[18px] h-[18px]" /></span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm font-medium text-surface-900 dark:text-white">Generate tagihan</span>
                                        <span class="block text-xs text-surface-500 dark:text-surface-400 truncate">Jalankan siklus billing otomatis</span>
                                    </span>
                                    <x-icon name="chevron-right" class="w-4 h-4 text-surface-300 group-hover:text-surface-500 transition-colors" />
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- PSB --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pemasangan Baru (PSB)</h3>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-surface-200 dark:divide-surface-800">
                        @foreach([['Hari ini', $psbToday], ['Bulan ini', $psbMonth], ['Tahun ' . now()->year, $psbYear]] as [$lbl, $val])
                            <div class="px-4 py-4 text-center">
                                <p class="text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">{{ number_format($val, 0, ',', '.') }}</p>
                                <p class="mt-0.5 text-xs text-surface-500 dark:text-surface-400">{{ $lbl }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Pemakaian & aktivitas ================= --}}
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            {{-- Pemakai terbesar --}}
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Pemakai Terbesar</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">5 sesi aktif dengan trafik tertinggi</p>
                    </div>
                </div>
                @if($topUsers->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-surface-500 dark:text-surface-400">Belum ada sesi aktif.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="w-10">#</th>
                                    <th>Username</th>
                                    <th class="text-right">Trafik</th>
                                    <th class="text-right">Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topUsers as $idx => $top)
                                    <tr>
                                        <td class="text-surface-400 tabular-nums">{{ $idx + 1 }}</td>
                                        <td>
                                            <p class="font-medium text-surface-900 dark:text-white font-mono text-[13px]">{{ $top->username }}</p>
                                            <p class="text-xs text-surface-400 font-mono">{{ $top->framedipaddress ?: '—' }}</p>
                                        </td>
                                        <td class="text-right font-medium tabular-nums text-surface-900 dark:text-white">{{ $bytes($top->total_traffic) }}</td>
                                        <td class="text-right tabular-nums">
                                            @php $secs = (int) $top->acctsessiontime; @endphp
                                            {{ $secs >= 86400 ? floor($secs / 86400) . 'h ' : '' }}{{ gmdate('H:i', $secs % 86400) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Aktivitas terbaru --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Aktivitas Terbaru</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Perubahan data & transaksi di sistem</p>
                    </div>
                    @if($isAdministrator)
                        <a href="{{ route('activity-logs.index') }}" class="btn-ghost btn-sm">Semua log <x-icon name="chevron-right" class="w-3.5 h-3.5" /></a>
                    @endif
                </div>
                <ul class="divide-y divide-surface-100 dark:divide-surface-800">
                    @forelse($latestActivities->take(6) as $activity)
                        @php
                            $badge = match ($activity->action) {
                                'created' => ['badge-success', 'Dibuat'],
                                'updated' => ['badge-info', 'Diubah'],
                                'deleted' => ['badge-danger', 'Dihapus'],
                                'login' => ['badge-primary', 'Login'],
                                'logout' => ['badge-gray', 'Logout'],
                                default => ['badge-gray', \Illuminate\Support\Str::headline((string) $activity->action)],
                            };
                        @endphp
                        <li class="flex items-start gap-3 px-5 py-3">
                            <span class="{{ $badge[0] }} mt-0.5 shrink-0 max-w-[9rem] truncate" title="{{ $badge[1] }}">{{ $badge[1] }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-surface-800 dark:text-surface-200 truncate" title="{{ $activity->description }}">{{ $activity->description }}</p>
                                <p class="text-xs text-surface-400 mt-0.5">{{ $activity->user->name ?? 'Sistem' }} · {{ $activity->created_at->locale('id')->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-surface-500 dark:text-surface-400">Belum ada aktivitas terbaru.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            // Jam realtime di header
            const clock = document.getElementById('live-clock');
            const tick = () => { if (clock) clock.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false }); };
            tick(); setInterval(tick, 1000);

            const el = document.getElementById('trafficChart');
            if (!el || typeof Chart === 'undefined') return;

            const isDark = document.documentElement.classList.contains('dark');
            const grid = isDark ? 'rgba(148,163,184,0.12)' : 'rgba(15,23,42,0.06)';
            const text = isDark ? '#94a3b8' : '#64748b';
            const c2d = el.getContext('2d');
            const fill = (rgb) => { const g = c2d.createLinearGradient(0, 0, 0, 288); g.addColorStop(0, `rgba(${rgb},0.18)`); g.addColorStop(1, `rgba(${rgb},0)`); return g; };

            Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';

            new Chart(el, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        { label: 'Download', data: @json($chartDownload), borderColor: '#10b981', backgroundColor: fill('16,185,129'), borderWidth: 2, fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 4 },
                        { label: 'Upload', data: @json($chartUpload), borderColor: '#6366f1', backgroundColor: fill('99,102,241'), borderWidth: 2, fill: true, tension: 0.35, pointRadius: 0, pointHoverRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { color: text, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 16, font: { size: 12 } } },
                        tooltip: {
                            backgroundColor: isDark ? '#0f172a' : '#ffffff', titleColor: isDark ? '#f8fafc' : '#0f172a', bodyColor: text,
                            borderColor: grid, borderWidth: 1, padding: 10, usePointStyle: true, boxPadding: 4,
                            callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y.toLocaleString('id-ID', { maximumFractionDigits: 2 })} MB` },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { color: text, font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 } },
                        y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: text, font: { size: 11 }, callback: (v) => v.toLocaleString('id-ID') + ' MB' } },
                    },
                },
            });
        })();
    </script>
    @endpush
</x-app-layout>
