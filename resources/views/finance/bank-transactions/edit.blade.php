<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('bank-accounts.show', $bankTransaction->bank_account_id) }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-emerald-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Edit Transaksi
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $bankTransaction->bankAccount->bank_name }} • {{ $bankTransaction->bankAccount->account_name }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl dark:bg-red-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-8 bg-amber-50 dark:bg-amber-900/10 border-b border-amber-100 dark:border-amber-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Edit Transaksi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ubah detail transaksi. Saldo dan jurnal akan dikoreksi otomatis.</p>
                        </div>
                    </div>
                </div>

                {{-- Current Info --}}
                <div class="px-8 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Tipe:</span>
                            <span class="ml-1 font-bold {{ $bankTransaction->type === 'deposit' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $bankTransaction->type === 'deposit' ? 'Debit (Masuk)' : 'Kredit (Keluar)' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Rekening:</span>
                            <span class="ml-1 font-bold text-gray-900 dark:text-white">{{ $bankTransaction->bankAccount->bank_name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Dibuat oleh:</span>
                            <span class="ml-1 font-bold text-gray-900 dark:text-white">{{ $bankTransaction->creator->name ?? 'System' }}</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('bank-transactions.update', $bankTransaction) }}" method="POST" class="p-8">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="col-span-full md:col-span-1">
                            <label for="display_amount" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Jumlah (Rp)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500">Rp</span>
                                </div>
                                <input type="text" id="display_amount" placeholder="0,00" class="w-full pl-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl shadow-sm focus:border-amber-500 focus:ring-amber-500 text-lg font-bold" oninput="formatCurrency(this, 'amount')" onblur="finalizeCurrency(this, 'amount')">
                                <input type="hidden" id="amount" name="amount" value="{{ old('amount', $bankTransaction->amount) }}">
                            </div>
                            @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="transaction_date" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tanggal Transaksi</label>
                            <input type="datetime-local" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', $bankTransaction->transaction_date->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                            @error('transaction_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Deskripsi / Keterangan</label>
                            <input type="text" id="description" name="description" value="{{ old('description', $bankTransaction->description) }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-amber-500 focus:ring-amber-500" required>
                            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="chart_of_account_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Kategori Akun (Buku Besar)</label>
                            <select id="chart_of_account_id" name="chart_of_account_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="">— Tanpa Kategori —</option>
                                @foreach($categories as $coa)
                                <option value="{{ $coa->id }}" @selected(old('chart_of_account_id', $bankTransaction->chart_of_account_id) == $coa->id)>
                                    {{ $coa->code }} - {{ $coa->name }} ({{ ucfirst($coa->type) }})
                                </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 italic">Opsional. Pilih akun untuk pencatatan di laporan keuangan.</p>
                            @error('chart_of_account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="reference_number" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nomor Referensi (Opsional)</label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number', $bankTransaction->reference_number) }}" placeholder="Contoh: REF-123" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            @error('reference_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('bank-accounts.show', $bankTransaction->bank_account_id) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            ← Kembali ke mutasi
                        </a>
                        <button type="submit" class="inline-flex items-center px-10 py-4 bg-amber-600 border border-transparent rounded-2xl font-bold text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:border-amber-900 focus:ring ring-amber-300 transition ease-in-out duration-150 shadow-lg shadow-amber-500/30">
                            Simpan Perubahan
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function formatCurrency(input, hiddenId) {
            let val = input.value.replace(/\./g, "").replace(",", ".");
            val = val.replace(/[^0-9.]/g, "");
            let parts = val.split(".");
            if (parts.length > 2) val = parts[0] + "." + parts.slice(1).join("");
            if (parts[1] && parts[1].length > 2) val = parts[0] + "." + parts[1].substring(0, 2);

            document.getElementById(hiddenId).value = val;
            
            if (val !== "") {
                let displayParts = val.split(".");
                let integerPart = new Intl.NumberFormat('id-ID').format(displayParts[0]);
                input.value = displayParts.length > 1 ? integerPart + "," + displayParts[1] : integerPart;
                if (val.endsWith(".") && !input.value.includes(",")) input.value += ",";
            } else {
                input.value = "";
            }
        }

        function finalizeCurrency(input, hiddenId) {
            let val = document.getElementById(hiddenId).value;
            if (val !== "") {
                let numeric = parseFloat(val);
                if (!isNaN(numeric)) {
                    input.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
                    document.getElementById(hiddenId).value = numeric.toFixed(2);
                }
            }
        }

        // Initialize amount display on load
        document.addEventListener('DOMContentLoaded', function() {
            const hiddenAmount = document.getElementById('amount');
            const displayAmount = document.getElementById('display_amount');
            if (hiddenAmount.value) {
                let numeric = parseFloat(hiddenAmount.value);
                if (!isNaN(numeric)) {
                    displayAmount.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
                }
            }
        });
    </script>
</x-app-layout>
