<x-app-layout>
    <div class="space-y-6 animate-fade-in pb-10">
        <!-- Greeting & Quick Profile -->
        <div class="flex items-center justify-between px-2">
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Selamat Datang</p>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ explode(' ', $customer->name)[0] }} 👋</h2>
            </div>
            <div class="w-12 h-12 rounded-full border-2 border-indigo-100 dark:border-indigo-900 p-0.5">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($customer->name) }}&background=6366f1&color=fff" class="w-full h-full rounded-full object-cover" alt="Profile">
            </div>
        </div>

        <!-- Main Service Status Card (MyIndiHome Style) -->
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-[2rem] p-6 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <span class="text-xs font-black uppercase tracking-widest">Internet Fiber</span>
                    </div>
                    <span class="px-3 py-1 bg-emerald-400 text-emerald-950 text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg shadow-emerald-500/20">
                        {{ $isOnline ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                </div>

                <div class="mb-6">
                    <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest mb-1">Paket Anda</p>
                    <h3 class="text-2xl font-black">{{ $customer->package->name }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-white/10 pt-6">
                    <div>
                        <p class="text-indigo-200 text-[9px] font-bold uppercase tracking-widest mb-1">ID Pelanggan</p>
                        <p class="text-sm font-black">{{ $customer->customer_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-200 text-[9px] font-bold uppercase tracking-widest mb-1">Kecepatan</p>
                        <p class="text-sm font-black">{{ $customer->package->speed_limit }} Mbps</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Koneksi Section -->
        <div class="px-2">
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Layanan Anda</h4>
        </div>

        <!-- Usage / Signal Status -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-black text-slate-900 dark:text-white text-sm">Status Koneksi</h4>
                <div class="w-2 h-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-rose-500' }} animate-pulse"></div>
            </div>
            
            <div class="space-y-4">
                @php $rx = $customer->signalCache->rx_power ?? '-'; @endphp
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 font-medium">Kualitas Sinyal</span>
                    <span class="text-xs font-black {{ $rx < -27 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $rx }} dBm</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                    @php 
                        $percentage = 0;
                        if(is_numeric($rx)) {
                            $percentage = max(0, min(100, (30 + (float)$rx) * 5)); // Just a rough visualization
                        }
                    @endphp
                    <div class="h-full {{ $rx < -27 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>

        <!-- Latest Billing Notification -->
        @if($unpaidInvoices->count() > 0)
            <div class="bg-rose-50 dark:bg-rose-900/20 rounded-3xl p-6 border border-rose-100 dark:border-rose-800 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-rose-100 dark:bg-rose-900/50 rounded-xl flex items-center justify-center text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-black text-rose-900 dark:text-rose-400">Tagihan Belum Dibayar</p>
                        <p class="text-[10px] text-rose-700/70 dark:text-rose-400/70">Total: Rp {{ number_format($unpaidInvoices->sum('amount'), 0, ',', '.') }}</p>
                    </div>
                </div>
                <a href="{{ route('customer.invoices') }}" class="text-[10px] font-black text-rose-600 uppercase tracking-widest underline">Bayar</a>
            </div>
        @endif

        <!-- Promo Banner -->
        <div class="relative rounded-3xl bg-slate-900 p-8 text-white overflow-hidden shadow-xl">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
            <div class="relative z-10">
                <span class="inline-block px-3 py-1 bg-indigo-600 rounded-lg text-[8px] font-black uppercase tracking-widest mb-3">Promo Eksklusif</span>
                <h4 class="text-xl font-black mb-2 leading-tight text-white">Undang Teman, <br> Diskon Rp 50.000!</h4>
                <p class="text-white/80 text-xs font-medium mb-4">Gunakan kode referral Anda.</p>
                <button class="bg-white text-slate-900 px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg">Cek Kode</button>
            </div>
        </div>

        <!-- Integrated Notification Prompt -->
        <div id="pwa-prompt" class="hidden bg-indigo-50 dark:bg-indigo-900/20 p-5 rounded-3xl border border-indigo-100 dark:border-indigo-800 flex items-center justify-between gap-4" x-data="{ dismissed: localStorage.getItem('pwa_prompt_dismissed') === 'true' }" x-show="!dismissed">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white flex-shrink-0 shadow-lg shadow-indigo-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </div>
                <div>
                    <h4 class="font-black text-indigo-950 dark:text-indigo-400 text-xs">Aktifkan Notifikasi</h4>
                    <p class="text-[10px] text-indigo-700/70 dark:text-indigo-400/70 leading-tight">Dapatkan info tagihan & promo terbaru.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="subscribeToPush()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-md active:scale-95 transition-all">Ya</button>
                <button @click="dismissed = true; localStorage.setItem('pwa_prompt_dismissed', 'true')" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Check for push subscription status and show prompt
        document.addEventListener('DOMContentLoaded', async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                document.getElementById('pwa-prompt').classList.remove('hidden');
            }
        });

        async function subscribeToPush() {
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: '{{ config('services.webpush.public_key') }}'
                });

                // Send to server
                await fetch('{{ route('customer.push.subscribe') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(subscription)
                });

                document.getElementById('pwa-prompt').classList.add('hidden');
                alert('Terima kasih! Notifikasi Anda telah aktif.');
            } catch (error) {
                console.error('Failed to subscribe:', error);
                alert('Gagal mengaktifkan notifikasi. Silakan coba lagi.');
            }
        }
    </script>
    @endpush
</x-app-layout>

