<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Chart of Accounts (Bagan Akun)
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Struktur keuangan untuk pencatatan Jurnal Umum dan Laporan Keuangan.
                </p>
            </div>
            <div class="flex gap-3">
                <button @click="$dispatch('open-modal', 'add-account')" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-indigo-500/20">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Akun Baru
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
        editAccount: { id: '', code: '', name: '', type: '', parent_id: '' },
        deleteAccount: { id: '', name: '', code: '' }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                @php
                    $types = [
                        'asset' => ['label' => 'Aset', 'color' => 'indigo', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        'liability' => ['label' => 'Kewajiban', 'color' => 'amber', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        'equity' => ['label' => 'Ekuitas', 'color' => 'emerald', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'income' => ['label' => 'Pendapatan', 'color' => 'blue', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                        'expense' => ['label' => 'Beban', 'color' => 'rose', 'icon' => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'],
                    ];
                @endphp

                @foreach($types as $key => $data)
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="p-2 bg-{{ $data['color'] }}-50 dark:bg-{{ $data['color'] }}-900/20 rounded-xl">
                            <svg class="w-5 h-5 text-{{ $data['color'] }}-600 dark:text-{{ $data['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $data['icon'] }}"></path></svg>
                        </div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $data['label'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Akun</p>
                    <h4 class="text-xl font-black text-gray-900 dark:text-white">{{ \App\Models\ChartOfAccount::where('type', $key)->count() }}</h4>
                </div>
                @endforeach
            </div>

            <!-- CoA Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        Daftar Rekening Buku Besar
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Akun</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Saldo</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($rootAccounts as $root)
                                @include('accounting.coa._account_row', [
                                    'account' => $root,
                                    'groupedAccounts' => $groupedAccounts,
                                    'depth' => 0,
                                    'types' => $types
                                ])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Tambah Akun (Minimal Implementation for Demo) -->
    <x-modal name="add-account" focusable>
        <form method="post" action="{{ route('accounting.coa.store') }}" class="p-8">
            @csrf
            <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6">Tambah Akun Baru</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Kode Akun</label>
                    <input type="text" name="code" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" placeholder="Contoh: 1101">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nama Akun</label>
                    <input type="text" name="name" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" placeholder="Contoh: Kas Kecil">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tipe</label>
                    <select name="type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="asset">Asset (Harta)</option>
                        <option value="liability">Liability (Kewajiban)</option>
                        <option value="equity">Equity (Modal)</option>
                        <option value="income">Income (Pendapatan)</option>
                        <option value="expense">Expense (Beban)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Induk (Optional)</label>
                    <select name="parent_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">-- Tanpa Induk --</option>
                        @foreach(\App\Models\ChartOfAccount::orderBy('code')->get() as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <button type="button" @click="$dispatch('close')" class="px-6 py-2 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">Simpan Akun</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal: Edit Akun -->
    <x-modal name="edit-account" focusable>
        <form method="post" :action="`{{ route('accounting.coa.index') }}/${editAccount.id}`" class="p-8">
            @csrf
            @method('PUT')
            <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6">Edit Akun</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Kode Akun</label>
                    <input type="text" name="code" x-model="editAccount.code" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nama Akun</label>
                    <input type="text" name="name" x-model="editAccount.name" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Tipe</label>
                    <select name="type" x-model="editAccount.type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="asset">Asset (Harta)</option>
                        <option value="liability">Liability (Kewajiban)</option>
                        <option value="equity">Equity (Modal)</option>
                        <option value="income">Income (Pendapatan)</option>
                        <option value="expense">Expense (Beban)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Induk (Optional)</label>
                    <select name="parent_id" x-model="editAccount.parent_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">-- Tanpa Induk --</option>
                        @foreach(\App\Models\ChartOfAccount::orderBy('code')->get() as $acc)
                            <option value="{{ $acc->id }}" x-show="editAccount.id != {{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-3">
                <button type="button" @click="$dispatch('close')" class="px-6 py-2 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">Simpan Perubahan</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal: Konfirmasi Hapus -->
    <x-modal name="confirm-account-deletion" focusable>
        <form method="post" :action="`{{ route('accounting.coa.index') }}/${deleteAccount.id}`" class="p-8">
            @csrf
            @method('DELETE')
            
            <h2 class="text-xl font-black text-gray-900 dark:text-white mb-4">Hapus Akun?</h2>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Apakah Anda yakin ingin menghapus akun <span class="font-bold text-gray-900 dark:text-white" x-text="`${deleteAccount.code} - ${deleteAccount.name}`"></span>? 
                Aksi ini tidak dapat dibatalkan jika akun tidak memiliki transaksi.
            </p>

            <div class="flex justify-end space-x-3">
                <button type="button" @click="$dispatch('close')" class="px-6 py-2 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">Batal</button>
                <button type="submit" class="px-6 py-2 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-colors shadow-lg shadow-rose-500/20">Hapus Sekarang</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
