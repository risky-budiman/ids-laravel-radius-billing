<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-surface-900 dark:text-white">Booster Kuota</h1>
            <p class="text-sm text-surface-500 dark:text-surface-400">Tambahan kuota saat pemakaian Anda mencapai batas FUP bulan ini.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @forelse($boosters as $booster)
                <div class="card p-5 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-surface-900 dark:text-white">{{ $booster->name }}</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-surface-900 dark:text-white">
                                {{ $booster->quota_gb }} <span class="text-sm font-medium text-surface-500">GB</span>
                            </p>
                        </div>
                        <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 shrink-0">
                            <x-icon name="bolt" class="w-5 h-5" />
                        </span>
                    </div>
                    <div class="mt-5 pt-4 border-t border-surface-200 dark:border-surface-800 flex items-center justify-between gap-3">
                        <p class="text-base font-semibold tabular-nums text-surface-900 dark:text-white">Rp {{ number_format($booster->price, 0, ',', '.') }}</p>
                        <form action="{{ route('customer.boosters.buy', $booster->id) }}" method="POST"
                              onsubmit="return confirm('Beli {{ addslashes($booster->name) }} ({{ $booster->quota_gb }} GB) seharga Rp {{ number_format($booster->price, 0, ',', '.') }}?')">
                            @csrf
                            <button type="submit" class="btn-primary btn-sm">Beli</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card sm:col-span-2 px-6 py-12 text-center">
                    <div class="mx-auto w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 mb-3">
                        <x-icon name="bolt" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Belum ada paket booster</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Paket booster akan tampil di sini bila tersedia.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
