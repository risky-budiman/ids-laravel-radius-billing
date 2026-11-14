@php
    $firstName = explode(' ', trim($customer->name))[0];
    $package = $customer->package;
    $statusMap = [
        'active' => ['badge-success', 'Aktif'],
        'new' => ['badge-info', 'Baru'],
        'waiting_activation' => ['badge-warning', 'Menunggu aktivasi'],
        'suspended' => ['badge-danger', 'Terisolir'],
        'waiting_dismantle' => ['badge-warning', 'Menunggu pencabutan'],
        'dismantled' => ['badge-gray', 'Dicabut'],
    ];
    $accountStatus = $statusMap[$customer->status ?? ''] ?? ($customer->is_active ? ['badge-success', 'Aktif'] : ['badge-gray', 'Nonaktif']);

    $rx = $customer->signalCache->rx_power ?? null;
    $rxNum = is_numeric($rx) ? (float) $rx : null;
    [$signalLabel, $signalTone] = match (true) {
        $rxNum === null => ['Tidak tersedia', 'text-surface-400'],
        $rxNum >= -24 => ['Sangat baik', 'text-emerald-600 dark:text-emerald-400'],
        $rxNum >= -27 => ['Baik', 'text-emerald-600 dark:text-emerald-400'],
        default => ['Lemah', 'text-rose-600 dark:text-rose-400'],
    };
    $signalPct = $rxNum === null ? 0 : max(5, min(100, (30 + $rxNum) * 6.5));

    $unpaidTotal = $unpaidInvoices->sum('amount');
    $nextInvoice = $unpaidInvoices->sortBy('due_date')->first();
@endphp

<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-5">

        {{-- Sapaan --}}
        <div>
            <p class="text-sm text-surface-500 dark:text-surface-400">Halo,</p>
            <h1 class="text-2xl font-semibold tracking-tight text-surface-900 dark:text-white">{{ $firstName }}</h1>
        </div>

        {{-- Tagihan belum dibayar: prioritas utama --}}
        @if($unpaidInvoices->count() > 0)
            <div class="card border-amber-200 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/5 p-5">
                <div class="flex items-start gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 shrink-0">
                        <x-icon name="document" class="w-5 h-5" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-amber-900 dark:text-amber-200">
                            {{ $unpaidInvoices->count() }} tagihan belum dibayar
                        </p>
                        <p class="mt-0.5 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</p>
                        @if($nextInvoice?->due_date)
                            <p class="mt-0.5 text-xs {{ $nextInvoice->due_date->isPast() ? 'text-rose-600 dark:text-rose-400' : 'text-amber-800/80 dark:text-amber-300/80' }}">
                                {{ $nextInvoice->due_date->isPast() ? 'Lewat jatuh tempo' : 'Jatuh tempo' }} {{ $nextInvoice->due_date->locale('id')->translatedFormat('d F Y') }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    @if($nextInvoice)
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('portal.invoice', $nextInvoice->id) }}" class="btn-primary flex-1">Bayar sekarang</a>
                    @endif
                    <a href="{{ route('customer.invoices') }}" class="btn-secondary">Lihat tagihan</a>
                </div>
            </div>
        @endif

        {{-- Layanan --}}
        <div class="card overflow-hidden">
            <div class="p-5 bg-primary-600 text-white">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-primary-100">Paket internet</p>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isOnline ? 'bg-emerald-300' : 'bg-surface-300' }}"></span>
                        {{ $isOnline ? 'Online' : 'Offline' }}
                    </span>
                </div>
                <p class="mt-1 text-xl font-semibold">{{ $package->name ?? 'Belum ada paket' }}</p>
                @if($package?->speed_limit)
                    <p class="text-sm text-primary-100">Hingga {{ $package->speed_limit }} Mbps</p>
                @endif
            </div>
            <dl class="grid grid-cols-2 divide-x divide-surface-200 dark:divide-surface-800">
                <div class="p-4">
                    <dt class="text-xs text-surface-500 dark:text-surface-400">ID Pelanggan</dt>
                    <dd class="mt-0.5 text-sm font-medium font-mono text-surface-900 dark:text-white">{{ $customer->customer_code }}</dd>
                </div>
                <div class="p-4">
                    <dt class="text-xs text-surface-500 dark:text-surface-400">Status akun</dt>
                    <dd class="mt-1"><span class="{{ $accountStatus[0] }}">{{ $accountStatus[1] }}</span></dd>
                </div>
            </dl>
        </div>

        {{-- Kualitas sinyal --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-surface-900 dark:text-white">Kualitas sinyal fiber</h2>
                <span class="text-sm font-medium {{ $signalTone }}">{{ $signalLabel }}</span>
            </div>
            <div class="mt-3 h-2 rounded-full bg-surface-100 dark:bg-surface-800 overflow-hidden">
                <div class="h-full rounded-full {{ $rxNum !== null && $rxNum < -27 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $signalPct }}%"></div>
            </div>
            <p class="mt-2 text-xs text-surface-500 dark:text-surface-400">
                @if($rxNum !== null)
                    Daya terima {{ number_format($rxNum, 2, ',', '.') }} dBm.
                    @if($rxNum < -27) Sinyal lemah dapat membuat koneksi lambat — silakan laporkan gangguan. @endif
                @else
                    Data sinyal belum tersedia.
                @endif
            </p>
        </div>

        {{-- Menu cepat --}}
        <div class="grid grid-cols-3 gap-3">
            @foreach([
                [route('customer.invoices'), 'document', 'Tagihan'],
                [route('customer.boosters'), 'bolt', 'Booster'],
                [route('customer.tickets.create'), 'support', 'Lapor gangguan'],
            ] as [$url, $ico, $lbl])
                <a href="{{ $url }}" class="card p-4 flex flex-col items-center gap-2 text-center hover:border-primary-300 dark:hover:border-primary-500/40 transition-colors">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-icon :name="$ico" class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-medium text-surface-700 dark:text-surface-200">{{ $lbl }}</span>
                </a>
            @endforeach
        </div>

        {{-- Ajakan aktifkan notifikasi --}}
        <div id="pwa-prompt" class="hidden card p-4" x-data="{ dismissed: localStorage.getItem('pwa_prompt_dismissed') === 'true', msg: '' }" x-show="!dismissed">
            <div class="flex items-start gap-3">
                <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 shrink-0">
                    <x-icon name="bell" class="w-5 h-5" />
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-surface-900 dark:text-white">Aktifkan notifikasi</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Dapatkan pengingat tagihan dan info gangguan.</p>
                    <p class="text-xs mt-1 text-primary-600 dark:text-primary-400" x-show="msg" x-text="msg" x-cloak></p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" onclick="subscribeToPush(this)" class="btn-primary btn-sm">Aktifkan</button>
                        <button type="button" @click="dismissed = true; localStorage.setItem('pwa_prompt_dismissed', 'true')" class="btn-ghost btn-sm">Nanti saja</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (!subscription) document.getElementById('pwa-prompt').classList.remove('hidden');
        });

        async function subscribeToPush(btn) {
            const box = document.getElementById('pwa-prompt');
            const setMsg = (m) => { if (box && box._x_dataStack) box._x_dataStack[0].msg = m; };
            try {
                btn.disabled = true;
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: '{{ config('services.webpush.public_key') }}'
                });
                await fetch('{{ route('customer.push.subscribe') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(subscription)
                });
                setMsg('Notifikasi aktif. Terima kasih!');
                setTimeout(() => box.classList.add('hidden'), 1500);
            } catch (error) {
                console.error('Failed to subscribe:', error);
                setMsg('Gagal mengaktifkan notifikasi. Pastikan izin notifikasi browser diizinkan.');
                btn.disabled = false;
            }
        }
    </script>
    @endpush
</x-app-layout>
