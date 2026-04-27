<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('bank-accounts.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-emerald-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Catat Pengeluaran Biaya
            </h2>
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
                <form action="{{ route('bank-transactions.process-expense') }}" method="POST" class="p-8">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="col-span-full">
                            <label for="bank_account_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Pilih Sumber Dana (Rekening/Kas)</label>
                            <select id="bank_account_id" name="bank_account_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required onchange="updateBalanceInfo(this)">
                                <option value="">Pilih Rekening Asal</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" data-balance="{{ $account->balance }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->bank_name }} - {{ $account->account_name }}
                                    @if($account->user) (Pegang oleh: {{ $account->user->name }}) @endif
                                    (Rp {{ number_format($account->balance, 0, ',', '.') }})
                                </option>
                                @endforeach
                            </select>
                            <div id="balance_display" class="mt-2 text-xs font-semibold text-gray-500"></div>
                            @error('bank_account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="display_amount" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Jumlah Pengeluaran (Rp)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500">Rp</span>
                                </div>
                                <input type="text" id="display_amount" placeholder="0,00" class="w-full pl-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-lg font-bold" oninput="formatCurrency(this, 'amount')" onblur="finalizeCurrency(this, 'amount')">
                                <input type="hidden" id="amount" name="amount" value="{{ old('amount') }}">
                            </div>
                            @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="transaction_date" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tanggal Pengeluaran</label>
                            <input type="datetime-local" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            @error('transaction_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Keterangan / Keperluan</label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="Contoh: Beli Bensin Teknisi, Makan Siang Team, Beli Kabel Patchcord" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="reference_number" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nomor Referensi / Nota (Opsional)</label>
                            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="Contoh: NOTA-123" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('reference_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" class="inline-flex items-center px-10 py-4 bg-red-600 border border-transparent rounded-2xl font-bold text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 transition ease-in-out duration-150 shadow-lg shadow-red-500/30">
                            Catat Pengeluaran
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
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

        function updateBalanceInfo(select) {
            const display = document.getElementById('balance_display');
            if (select.value) {
                const balance = select.options[select.selectedIndex].getAttribute('data-balance');
                const formatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(balance);
                display.innerHTML = `Saldo tersedia: <span class="text-gray-900 dark:text-gray-100 font-bold">${formatted}</span>`;
            } else {
                display.innerHTML = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateBalanceInfo(document.getElementById('bank_account_id'));
        });
    </script>
</x-app-layout>
