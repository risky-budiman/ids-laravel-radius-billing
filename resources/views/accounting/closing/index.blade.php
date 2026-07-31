<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
            History Tutup Buku & Laporan Bulanan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl dark:bg-emerald-900/30 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-tighter">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl dark:bg-red-900/30 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-red-800 dark:text-red-300 uppercase tracking-tighter">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Current Status & Warning -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-8">
                        <div class="p-8 bg-amber-50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-800/50">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Governance</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Status Kunci Periode</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="mb-6 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs font-medium text-gray-400 uppercase tracking-widest">Terkunci Hingga</span>
                                    <span class="text-xl font-black text-gray-900 dark:text-white">
                                        {{ $closedUntil ? \Carbon\Carbon::parse($closedUntil)->translatedFormat('d F Y') : 'Belum Terkunci' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800">
                                <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                                    <strong>Info:</strong> Tutup buku akan mengambil "Snapshot" nilai keuangan (Laba Rugi & Neraca) pada akhir bulan tersebut dan mengunci transaksi agar tidak bisa diubah kembali.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-8 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg">Periode Akuntansi (12 Bulan Terakhir)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Periode</th>
                                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Net Profit</th>
                                        <th class="px-8 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($periods as $period)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $period->period_string }}</div>
                                            <div class="text-xs text-gray-500">{{ $period->year }}</div>
                                        </td>
                                        <td class="px-8 py-6">
                                            @if($period->is_closed)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                                    CLOSED
                                                </span>
                                                <div class="text-[10px] text-gray-400 mt-1">Oleh: {{ $period->user->name ?? 'System' }}</div>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H15V7a5 5 0 00-5-5zM8 7a2 2 0 114 0v2H8V7z"></path></svg>
                                                    OPEN
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            @if($period->is_closed)
                                                <div class="font-bold {{ $period->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                    Rp {{ number_format($period->net_profit, 0, ',', '.') }}
                                                </div>
                                                <div class="text-[10px] text-gray-400">Equity Snapshot: Rp {{ number_format($period->total_equity, 0, ',', '.') }}</div>
                                            @else
                                                <span class="text-gray-300 italic text-sm">Waiting...</span>
                                            @endif
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            @if(!$period->is_closed)
                                                <button onclick="confirmClosing('{{ $period->id }}', '{{ $period->period_string }}')" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-xs font-bold rounded-xl hover:bg-amber-700 transition shadow-sm">
                                                    Tutup Buku
                                                </button>
                                            @else
                                                <div class="flex flex-col gap-1 items-end">
                                                    <a href="{{ route('accounting.reports.profit-loss', ['start_date' => \Carbon\Carbon::create($period->year, $period->month, 1)->startOfMonth()->toDateString(), 'end_date' => \Carbon\Carbon::create($period->year, $period->month, 1)->endOfMonth()->toDateString()]) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                        Laba Rugi
                                                    </a>
                                                    <a href="{{ route('accounting.reports.balance-sheet', ['date' => \Carbon\Carbon::create($period->year, $period->month, 1)->endOfMonth()->toDateString()]) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                        Neraca
                                                    </a>
                                                    @if(auth()->user()->isAdministrator())
                                                        <button onclick="confirmReopen('{{ $period->id }}', '{{ $period->period_string }}')" class="mt-2 text-rose-600 hover:text-rose-800 text-xs font-bold flex items-center gap-1 transition">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                            Buka Kembali
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="closingModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('accounting.closing.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="period_id" id="modalPeriodId">
                    
                    <div class="p-8">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-amber-100 text-amber-600 rounded-2xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Konfirmasi Tutup Buku</h3>
                        </div>
                        
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Apakah Anda yakin ingin menutup buku periode <strong id="modalPeriodName" class="text-gray-900 dark:text-white"></strong>? 
                            Tindakan ini akan mengunci semua transaksi pada bulan tersebut dan tidak dapat diubah kembali.
                        </p>

                        <div class="mb-6">
                            <label class="flex items-start cursor-pointer group">
                                <input type="checkbox" name="confirmation" class="mt-1 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" required>
                                <span class="ml-3 text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 transition">
                                    Saya mengonfirmasi bahwa data keuangan bulan ini sudah valid dan siap untuk dikunci (Final).
                                </span>
                            </label>
                        </div>

                         <div class="flex gap-4">
                            <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-2xl hover:bg-gray-200 transition uppercase tracking-widest text-xs">
                                Batal
                            </button>
                            <button type="submit" class="flex-1 px-6 py-3 bg-amber-600 text-white font-bold rounded-2xl hover:bg-amber-700 transition uppercase tracking-widest text-xs shadow-lg shadow-amber-500/30">
                                Ya, Tutup Buku
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Buka Kembali -->
    @if(auth()->user()->isAdministrator())
    <div id="reopenModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="reopenForm" action="" method="POST">
                    @csrf
                    
                    <div class="p-8">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-rose-100 text-rose-600 rounded-2xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Buka Kembali Periode</h3>
                        </div>
                        
                        <p class="text-gray-500 dark:text-gray-400 mb-6">
                            Apakah Anda yakin ingin membuka kembali periode <strong id="modalReopenPeriodName" class="text-gray-900 dark:text-white"></strong>? 
                            Membuka kembali periode akan menonaktifkan proteksi penguncian transaksi untuk bulan tersebut sehingga transaksi/jurnal dapat diubah atau dihapus kembali.
                        </p>

                        <div class="flex gap-4">
                            <button type="button" onclick="closeReopenModal()" class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-2xl hover:bg-gray-200 transition uppercase tracking-widest text-xs">
                                Batal
                            </button>
                            <button type="submit" class="flex-1 px-6 py-3 bg-rose-600 text-white font-bold rounded-2xl hover:bg-rose-700 transition uppercase tracking-widest text-xs shadow-lg shadow-rose-500/30">
                                Ya, Buka Kembali
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
        function confirmClosing(id, name) {
            document.getElementById('modalPeriodId').value = id;
            document.getElementById('modalPeriodName').innerText = name;
            document.getElementById('closingModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('closingModal').classList.add('hidden');
        }

        function confirmReopen(id, name) {
            const url = `{{ route('accounting.closing.reopen', ':id') }}`.replace(':id', id);
            document.getElementById('reopenForm').action = url;
            document.getElementById('modalReopenPeriodName').innerText = name;
            document.getElementById('reopenModal').classList.remove('hidden');
        }

        function closeReopenModal() {
            document.getElementById('reopenModal').classList.add('hidden');
        }
    </script>
    @endpush
</x-app-layout>
