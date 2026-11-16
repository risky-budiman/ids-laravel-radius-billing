@php
    $u = auth()->user();
    $canTech = $u->isAdmin() || $u->isTeknisi();
    $isCs = $u->isCs();
    // Ajukan ganti paket (disetujui admin/administrator): admin, CS, teknisi & sales. Ubah kontak: admin & CS
    $canRequestPackage = ($u->isAdmin() || $isCs || $u->isTeknisi() || $u->isSales()) && ! in_array($customer->status, \App\Models\Customer::TERMINATED_STATUSES, true);
    $pendingPackageRequest = \App\Models\PackageChangeRequest::with('toPackage:id,name')->where('customer_id', $customer->id)->where('status', 'pending')->first();
    $statusMap = [
        'active' => ['badge-success', 'Aktif'],
        'new' => ['badge-info', 'Baru'],
        'waiting_activation' => ['badge-warning', 'Menunggu aktivasi'],
        'suspended' => ['badge-danger', 'Terisolir'],
        'waiting_dismantle' => ['badge-warning', 'Menunggu dismantle'],
        'dismantled' => ['badge-gray', 'Dicabut'],
        'canceled' => ['badge-gray', 'Batal'],
    ];
    [$statusBadge, $statusLabel] = $statusMap[$customer->status ?? ''] ?? ($customer->is_active ? ['badge-success', 'Aktif'] : ['badge-gray', 'Nonaktif']);
    $typeLabel = ['personal' => 'Personal', 'corporate' => 'Korporat', 'vip' => 'VIP'][$customer->customer_type] ?? ucfirst((string) $customer->customer_type);
    $initials = collect(preg_split('/\s+/', trim($customer->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $d = fn ($date, $fmt = 'd M Y') => $date ? \Carbon\Carbon::parse($date)->locale('id')->translatedFormat($fmt) : '—';
    $isOnline = $sessions->contains(fn ($s) => ! $s->acctstoptime);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('customers.index') }}" class="btn-ghost btn-icon -ml-2 shrink-0" aria-label="Kembali ke daftar pelanggan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="min-w-0">
                    <h2 class="flex flex-wrap items-center gap-2">
                        <span class="truncate">{{ $customer->name }}</span>
                        <span class="{{ $statusBadge }}">{{ $statusLabel }}</span>
                    </h2>
                    <p class="page-subtitle font-mono">{{ $customer->customer_code ?: '—' }} · {{ $customer->username }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($canTech)
                    @if($customer->is_active && $modem)
                        <a href="{{ route('customers.dismantle', $customer) }}" class="btn-secondary !text-rose-600 dark:!text-rose-400">Dismantle</a>
                    @endif
                    @if(!$customer->is_active)
                        <a href="{{ route('customers.activate', $customer) }}" class="btn-success">Aktivasi sekarang</a>
                    @endif
                @endif
                @if($u->isAdmin() || $isCs)
                    <button type="button" x-data @click="$dispatch('open-modal', 'edit-contact')" class="btn-secondary">Ubah kontak</button>
                @endif
                @if($canRequestPackage && ! $pendingPackageRequest)
                    <button type="button" x-data @click="$dispatch('open-modal', 'request-package')" class="btn-secondary">Ajukan ganti paket</button>
                @endif
                @unless($isCs)
                    <a href="{{ route('customers.edit', $customer) }}" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit pelanggan
                    </a>
                @endunless
            </div>
        </div>
    </x-slot>

    @foreach(['success' => 'emerald', 'error' => 'rose'] as $flashKey => $tone)
        @if(session($flashKey))
            <div class="mb-4 rounded-xl border border-{{ $tone }}-200 bg-{{ $tone }}-50 px-4 py-3 text-sm text-{{ $tone }}-800 dark:border-{{ $tone }}-500/30 dark:bg-{{ $tone }}-500/10 dark:text-{{ $tone }}-300">{{ session($flashKey) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
    @endif
    @if($pendingPackageRequest)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            Pengajuan ganti paket ke <strong>{{ $pendingPackageRequest->toPackage?->name }}</strong> menunggu persetujuan admin.
            <a href="{{ route('package-requests.index') }}" class="font-semibold underline">Lihat pengajuan</a>
        </div>
    @endif

    @if($u->isAdmin() || $isCs)
        <x-modal name="edit-contact" focusable>
            <form method="POST" action="{{ route('customers.contact.update', $customer) }}" class="p-6 space-y-4">
                @csrf @method('PATCH')
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">Ubah kontak pelanggan</h2>
                <div>
                    <label for="c_phone" class="label">Nomor HP / WhatsApp</label>
                    <input id="c_phone" name="phone" class="input" value="{{ old('phone', $customer->phone) }}" maxlength="20">
                    <p class="help-text">Dipakai untuk tagihan, pengingat & kode OTP portal.</p>
                </div>
                <div>
                    <label for="c_email" class="label">Email</label>
                    <input id="c_email" name="email" type="email" class="input" value="{{ old('email', $customer->email) }}">
                </div>
                <div>
                    <label for="c_address" class="label">Alamat</label>
                    <textarea id="c_address" name="address" rows="3" class="input !h-auto" maxlength="500">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-ghost">Batal</button>
                    <button class="btn-primary">Simpan</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if($canRequestPackage && ! $pendingPackageRequest)
        <x-modal name="request-package" focusable>
            <form method="POST" action="{{ route('package-requests.store', $customer) }}" class="p-6 space-y-4">
                @csrf
                <h2 class="text-lg font-semibold text-surface-900 dark:text-white">Ajukan ganti paket</h2>
                <p class="text-sm text-surface-500">Paket saat ini: <strong>{{ $customer->package?->name ?? '—' }}</strong>{{ $customer->package ? ' (Rp ' . number_format((float) $customer->package->price, 0, ',', '.') . ')' : '' }}. Paket berganti setelah disetujui admin.</p>
                <div>
                    <label for="to_package_id" class="label">Paket baru</label>
                    <select id="to_package_id" name="to_package_id" class="input" required>
                        <option value="">Pilih paket</option>
                        @foreach(\App\Models\Package::orderBy('price')->get(['id', 'name', 'price']) as $pk)
                            @continue($pk->id === $customer->package_id)
                            <option value="{{ $pk->id }}">{{ $pk->name }} — Rp {{ number_format((float) $pk->price, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reason" class="label">Alasan / permintaan pelanggan</label>
                    <textarea id="reason" name="reason" rows="3" class="input !h-auto" maxlength="500" required placeholder="mis. Pelanggan minta naik ke 20 Mbps mulai bulan depan"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-ghost">Batal</button>
                    <button class="btn-primary">Kirim pengajuan</button>
                </div>
            </form>
        </x-modal>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- ================= Kolom kiri ================= --}}
        <div class="space-y-6">
            {{-- Profil --}}
            <div class="card">
                <div class="p-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 flex items-center justify-center text-base font-semibold shrink-0">{{ $initials }}</div>
                    <div class="min-w-0">
                        <p class="font-semibold text-surface-900 dark:text-white truncate">{{ $customer->name }}</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span class="badge-gray">{{ $typeLabel }}</span>
                            <span class="badge {{ $isOnline ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-surface-50 text-surface-500 ring-surface-500/20 dark:bg-surface-400/10 dark:text-surface-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-surface-400' }}"></span>{{ $isOnline ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                    </div>
                </div>
                <dl class="border-t border-surface-200 dark:border-surface-800 divide-y divide-surface-100 dark:divide-surface-800 text-sm">
                    <div class="flex justify-between gap-4 px-5 py-2.5">
                        <dt class="text-surface-500 dark:text-surface-400">Paket</dt>
                        <dd class="font-medium text-surface-900 dark:text-white text-right">{{ $customer->package->name ?? '—' }}</dd>
                    </div>
                    @if(!empty($customer->discount_value) && $customer->discount_value > 0)
                        <div class="flex justify-between gap-4 px-5 py-2.5">
                            <dt class="text-surface-500 dark:text-surface-400">Diskon tetap</dt>
                            <dd><span class="badge-success">{{ $customer->discount_type === 'percentage' ? rtrim(rtrim(number_format($customer->discount_value, 2, ',', '.'), '0'), ',') . '%' : rupiah($customer->discount_value) }}</span></dd>
                        </div>
                    @endif
                    @if($canTech)
                        <div class="flex justify-between items-center gap-4 px-5 py-2.5" x-data="{ show: false }">
                            <dt class="text-surface-500 dark:text-surface-400">Password PPPoE</dt>
                            <dd class="flex items-center gap-1">
                                <span class="font-mono text-surface-900 dark:text-white" x-text="show ? @js($pppoePassword ?? '—') : '••••••••'">••••••••</span>
                                @if($pppoePassword)
                                    <button type="button" @click="show = !show" class="p-1 rounded text-surface-400 hover:text-surface-700 dark:hover:text-surface-200" :aria-label="show ? 'Sembunyikan' : 'Tampilkan'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                @endif
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 px-5 py-2.5">
                        <dt class="text-surface-500 dark:text-surface-400">Terdaftar</dt>
                        <dd class="text-surface-700 dark:text-surface-300">{{ $d($customer->created_at, 'd M Y') }}</dd>
                    </div>
                    @if($customer->activated_at)
                        <div class="flex justify-between gap-4 px-5 py-2.5">
                            <dt class="text-surface-500 dark:text-surface-400">Aktif sejak</dt>
                            <dd class="text-surface-700 dark:text-surface-300">{{ $d($customer->activated_at, 'd M Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Kontak --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">Kontak</h3></div>
                <dl class="p-5 space-y-3 text-sm">
                    @foreach([
                        ['user', 'No. KTP', $customer->ktp, true],
                        ['phone', 'Telepon', $customer->phone, false],
                        ['inbox', 'Email', $customer->email, false],
                        ['map', 'Alamat', $customer->address, false],
                    ] as [$ico, $lbl, $val, $mono])
                        <div class="flex items-start gap-3">
                            <x-icon :name="$ico" class="w-4 h-4 mt-0.5 text-surface-400 shrink-0" />
                            <div class="min-w-0">
                                <dt class="text-xs text-surface-500 dark:text-surface-400">{{ $lbl }}</dt>
                                <dd class="{{ $val ? 'text-surface-800 dark:text-surface-200' : 'text-surface-400 italic' }} {{ $mono && $val ? 'font-mono' : '' }} break-words">{{ $val ?: 'Belum diisi' }}</dd>
                            </div>
                        </div>
                    @endforeach
                    @if($customer->phone)
                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $customer->phone)) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm w-full mt-2">Chat WhatsApp</a>
                    @endif
                </dl>
            </div>

            {{-- FUP --}}
            @if(get_setting('enable_fup_module') == '1' && $customer->package && $customer->package->enable_fup)
                @php
                    $limit = (float) $customer->package->fup_limit_gb;
                    $pct = $limit > 0 ? ($customer->current_month_usage_gb / $limit) * 100 : 0;
                    $bar = $pct >= 100 ? 'bg-rose-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pemakaian FUP bulan ini</h3>
                        <span class="text-sm font-medium {{ $pct >= 100 ? 'text-rose-600' : 'text-surface-600 dark:text-surface-300' }}">{{ number_format($pct, 1, ',', '.') }}%</span>
                    </div>
                    <div class="card-body space-y-3">
                        <div class="h-2 rounded-full bg-surface-100 dark:bg-surface-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $bar }}" style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                        <p class="text-sm text-surface-600 dark:text-surface-300">
                            <span class="font-semibold text-surface-900 dark:text-white">{{ $customer->current_month_usage_gb }} GB</span> dari {{ $customer->package->fup_limit_gb }} GB
                        </p>
                        <p class="text-xs text-surface-400">Sinkron terakhir: {{ $customer->last_usage_sync ? $customer->last_usage_sync->locale('id')->diffForHumans() : 'belum pernah' }}</p>
                        @if($u->isAdmin())
                            <form action="{{ route('customers.reset-fup', $customer) }}" method="POST" onsubmit="return confirm('Reset pemakaian ke 0 dan kembalikan kecepatan normal?')">
                                @csrf
                                <button type="submit" class="btn-secondary btn-sm w-full">Reset FUP</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ================= Kolom kanan: tab ================= --}}
        <div class="xl:col-span-2" x-data="{ tab: 'billing' }">
            <div class="flex gap-1 border-b border-surface-200 dark:border-surface-800 overflow-x-auto" role="tablist">
                @foreach(['billing' => 'Tagihan', 'assets' => 'Perangkat', 'enterprise' => 'Infrastruktur & KYC', 'sessions' => 'Riwayat sesi'] as $key => $label)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                            class="px-3 py-2.5 -mb-px border-b-2 text-sm font-medium whitespace-nowrap transition-colors"
                            :class="tab === '{{ $key }}' ? 'border-primary-600 text-primary-700 dark:text-primary-400' : 'border-transparent text-surface-500 hover:text-surface-800 dark:hover:text-surface-200'">
                        {{ $label }}
                        @if($key === 'billing' && $unpaidTotal > 0)
                            <span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-amber-500 align-middle"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Tagihan --}}
            <div x-show="tab === 'billing'" class="pt-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-stat-card label="Belum dibayar" :value="rupiah($unpaidTotal)" icon="exclamation" :tone="$unpaidTotal > 0 ? 'warning' : 'gray'" />
                    <x-stat-card label="Total invoice" :value="$invoices->count() . ($invoices->count() >= 24 ? '+' : '')" icon="document" tone="gray" :hint="$invoices->count() >= 24 ? 'Menampilkan 24 terakhir' : null" />
                    <x-stat-card label="Pembayaran terakhir" :value="$d($invoices->where('status', 'paid')->sortByDesc('paid_at')->first()?->paid_at)" icon="check-circle" tone="success" />
                </div>
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table whitespace-nowrap">
                            <thead>
                                <tr><th>Invoice</th><th>Periode</th><th>Jatuh tempo</th><th class="text-right">Jumlah</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                    @php $overdue = $inv->status === 'unpaid' && $inv->due_date && $inv->due_date->isPast(); @endphp
                                    <tr>
                                        <td><a href="{{ route('invoices.show', $inv) }}" class="font-medium text-surface-900 dark:text-white hover:text-primary-600 font-mono text-[13px]">{{ $inv->invoice_number }}</a></td>
                                        <td class="text-surface-500">{{ $inv->period_start ? $d($inv->period_start, 'M Y') : ($inv->billing_period ?: '—') }}</td>
                                        <td class="{{ $overdue ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ $d($inv->due_date) }}</td>
                                        <td class="text-right tabular-nums font-medium text-surface-900 dark:text-white">{{ rupiah($inv->amount) }}</td>
                                        <td>
                                            @if($inv->status === 'paid')<span class="badge-success">Lunas</span>
                                            @elseif($inv->status === 'written_off')<span class="badge-gray">Dihapus buku</span>
                                            @elseif($overdue)<span class="badge-danger">Terlambat</span>
                                            @else<span class="badge-warning">Belum bayar</span>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="!py-10 text-center text-surface-500">Belum ada invoice untuk pelanggan ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-surface-200 dark:border-surface-800 text-right">
                        <a href="{{ route('invoices.index', ['search' => $customer->name]) }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">Lihat semua di menu Invoice</a>
                    </div>
                </div>
            </div>

            {{-- Perangkat --}}
            <div x-show="tab === 'assets'" x-cloak class="pt-5 space-y-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Perangkat utama (CPE)</h3></div>
                    <div class="card-body">
                        @if($modem)
                            <div class="flex items-center gap-4">
                                <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="monitor" class="w-5 h-5" /></span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-surface-900 dark:text-white">{{ $modem->item->name ?? 'Perangkat' }}</p>
                                    <p class="text-xs font-mono text-surface-500">SN {{ $modem->serial_number }}</p>
                                </div>
                                <span class="badge-primary">Terpasang</span>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <p class="text-sm text-surface-500">Belum ada perangkat tercatat.</p>
                                @if($canTech && !$customer->is_active)
                                    <a href="{{ route('customers.activate', $customer) }}" class="btn-secondary btn-sm mt-3">Jalankan wizard aktivasi</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card overflow-hidden">
                    <div class="card-header"><h3 class="card-title">Material terpakai</h3></div>
                    <table class="table">
                        <tbody>
                            @forelse($materials as $material)
                                <tr>
                                    <td>
                                        <p class="font-medium text-surface-900 dark:text-white">{{ $material->item->name ?? 'Barang' }}</p>
                                        <p class="text-xs text-surface-400">{{ $material->reference }}</p>
                                    </td>
                                    <td class="text-right tabular-nums">{{ rtrim(rtrim(number_format($material->quantity, 1, ',', '.'), '0'), ',') }} {{ $material->item->unit ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="!py-8 text-center text-surface-500">Belum ada material tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Infrastruktur & KYC --}}
            <div x-show="tab === 'enterprise'" x-cloak class="pt-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([
                        'Infrastruktur fisik' => [
                            'ODC' => $customer->odc->name ?? null,
                            'ODP' => $customer->odp->name ?? null,
                            'Port ODP' => $customer->odp_port,
                            'Panjang drop core' => $customer->cable_length ? $customer->cable_length . ' m' : null,
                        ],
                        'Konfigurasi CPE & logis' => [
                            'VLAN layanan' => $customer->vlan_id,
                            'IP statis' => $customer->static_ip ?: 'Dinamis',
                            'Merek CPE' => $customer->cpe_brand,
                            'MAC CPE' => $customer->cpe_mac,
                        ],
                    ] as $title => $rows)
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
                            <dl class="divide-y divide-surface-100 dark:divide-surface-800 text-sm">
                                @foreach($rows as $k => $v)
                                    <div class="flex justify-between gap-4 px-5 py-2.5">
                                        <dt class="text-surface-500 dark:text-surface-400">{{ $k }}</dt>
                                        <dd class="text-right {{ $v ? 'font-medium text-surface-900 dark:text-white' : 'text-surface-400' }} {{ $k === 'MAC CPE' ? 'font-mono' : '' }}">{{ $v ?: '—' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Dokumen KYC & foto verifikasi</h3></div>
                    <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach(['identity_photo' => 'Identitas (KTP)', 'house_photo' => 'Rumah / lokasi', 'cpe_photo' => 'Perangkat (CPE)'] as $field => $label)
                            <figure>
                                <figcaption class="text-xs text-surface-500 dark:text-surface-400 mb-1.5">{{ $label }}</figcaption>
                                @if($customer->$field)
                                    <a href="{{ asset('storage/' . $customer->$field) }}" target="_blank" rel="noopener" class="block aspect-video rounded-lg overflow-hidden border border-surface-200 dark:border-surface-700 hover:opacity-90">
                                        <img src="{{ asset('storage/' . $customer->$field) }}" alt="{{ $label }}" class="w-full h-full object-cover">
                                    </a>
                                @else
                                    <div class="aspect-video rounded-lg border border-dashed border-surface-300 dark:border-surface-700 flex items-center justify-center text-xs text-surface-400">Belum ada foto</div>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>

                @if($customer->description)
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Catatan</h3></div>
                        <p class="card-body text-sm text-surface-700 dark:text-surface-300 whitespace-pre-wrap">{{ $customer->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Riwayat sesi --}}
            <div x-show="tab === 'sessions'" x-cloak class="pt-5">
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th>Mulai</th><th>Selesai</th><th>Durasi</th><th>IP</th><th>MAC</th><th>NAS</th><th>Penyebab putus</th><th class="text-right">Trafik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sessions as $session)
                                    @php $secs = (int) $session->acctsessiontime; @endphp
                                    <tr>
                                        <td><span class="text-surface-900 dark:text-white">{{ $session->acctstarttime?->format('d/m/Y') }}</span> <span class="text-xs text-surface-400">{{ $session->acctstarttime?->format('H:i') }}</span></td>
                                        <td>
                                            @if($session->acctstoptime)
                                                <span class="text-surface-900 dark:text-white">{{ $session->acctstoptime->format('d/m/Y') }}</span> <span class="text-xs text-surface-400">{{ $session->acctstoptime->format('H:i') }}</span>
                                            @else
                                                <span class="badge-success">Sedang online</span>
                                            @endif
                                        </td>
                                        <td class="tabular-nums">{{ $secs >= 86400 ? floor($secs / 86400) . 'h ' : '' }}{{ gmdate('H:i:s', $secs % 86400) }}</td>
                                        <td class="font-mono text-xs">{{ $session->framedipaddress ?: '—' }}</td>
                                        <td class="font-mono text-xs text-surface-500">{{ $session->callingstationid ?: '—' }}</td>
                                        <td class="text-xs text-surface-500">{{ $session->nasipaddress }}</td>
                                        <td class="text-xs text-surface-500">{{ $session->acctterminatecause ?: '—' }}</td>
                                        <td class="text-right text-xs tabular-nums">
                                            <span class="text-emerald-600 dark:text-emerald-400">↓ {{ number_format($session->acctoutputoctets / 1048576, 1, ',', '.') }} MB</span><br>
                                            <span class="text-primary-600 dark:text-primary-400">↑ {{ number_format($session->acctinputoctets / 1048576, 1, ',', '.') }} MB</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="!py-10 text-center text-surface-500">Belum ada riwayat sesi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
