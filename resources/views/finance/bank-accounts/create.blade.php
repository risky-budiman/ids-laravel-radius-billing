<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('bank-accounts.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-emerald-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Tambah Rekening Baru
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <form action="{{ route('bank-accounts.store') }}" method="POST" class="p-8">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="col-span-full md:col-span-1">
                            <label for="bank_name" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nama Bank / Kas</label>
                            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" placeholder="Contoh: BCA, Kas Kecil, Midtrans" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            @error('bank_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="type" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tipe Rekening</label>
                            <select id="type" name="type" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                <option value="bank">Bank Konvensional</option>
                                <option value="cash">Uang Tunai / Kas</option>
                                <option value="payment_gateway">Payment Gateway</option>
                                <option value="e_wallet">E-Wallet</option>
                            </select>
                            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="user_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Penanggung Jawab / Staff (Opsional)</label>
                            <select id="user_id" name="user_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Tanpa Staff (Akun Utama/Kantor)</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->role }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Pilih jika kas ini dipegang oleh teknisi/admin tertentu.</p>
                            @error('user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="account_name" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Atas Nama</label>
                            <input type="text" id="account_name" name="account_name" value="{{ old('account_name') }}" placeholder="Contoh: PT ISP Maju" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            @error('account_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="account_number" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nomor Rekening</label>
                            <input type="text" id="account_number" name="account_number" value="{{ old('account_number') }}" placeholder="Hanya angka" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500" oninput="this.value = this.value.replace(/\D/g, '')">
                            @error('account_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full md:col-span-1">
                            <label for="chart_of_account_id" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Link ke Akun Perkiraan (CoA)</label>
                            <select id="chart_of_account_id" name="chart_of_account_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Pilih Akun (Otomatis: 1102 Bank)</option>
                                @foreach($accounts as $coa)
                                <option value="{{ $coa->id }}" {{ old('chart_of_account_id') == $coa->id ? 'selected' : '' }}>{{ $coa->code }} - {{ $coa->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Hubungkan rekening ini dengan akun di Buku Besar.</p>
                            @error('chart_of_account_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="display_initial_balance" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Saldo Awal (Rp)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500">Rp</span>
                                </div>
                                <input type="text" id="display_initial_balance" placeholder="0,00" class="w-full pl-12 border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500 font-bold text-lg" oninput="formatCurrency(this, 'initial_balance')" onblur="finalizeCurrency(this, 'initial_balance')">
                                <input type="hidden" id="initial_balance" name="initial_balance" value="{{ old('initial_balance') }}">
                            </div>
                            <p class="mt-1 text-xs text-gray-500 italic">Saldo awal akan dicatat sebagai mutasi setoran pertama.</p>
                            @error('initial_balance') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-full">
                            <label for="description" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Keterangan (Opsional)</label>
                            <textarea id="description" name="description" rows="3" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" class="inline-flex items-center px-8 py-3 bg-emerald-600 border border-transparent rounded-xl font-bold text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:border-emerald-900 focus:ring ring-emerald-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg shadow-emerald-500/30">
                            Simpan Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

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

    document.addEventListener('DOMContentLoaded', function() {
        const hidden = document.getElementById('initial_balance');
        const display = document.getElementById('display_initial_balance');
        if (hidden && hidden.value) {
            let numeric = parseFloat(hidden.value);
            if (!isNaN(numeric)) {
                display.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric);
            }
        }
    });
</script>
