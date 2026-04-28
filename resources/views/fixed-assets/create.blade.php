<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('fixed-assets.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 dark:text-white tracking-tight">
                Register Fixed Asset
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto py-6">
        <form action="{{ route('fixed-assets.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Asset Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="name" value="Asset Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. Server Mikrotik CCR1036" required />
                    </div>

                    <div>
                        <x-input-label for="purchase_date" value="Purchase Date" />
                        <x-text-input id="purchase_date" name="purchase_date" type="date" class="mt-1 block w-full" value="{{ date('Y-m-d') }}" required />
                    </div>

                    <div>
                        <x-input-label for="useful_life_months" value="Useful Life (Months)" />
                        <x-text-input id="useful_life_months" name="useful_life_months" type="number" min="1" class="mt-1 block w-full" placeholder="e.g. 60 (5 years)" required />
                        <p class="text-xs text-gray-500 mt-1">Estimasi umur barang sebelum nilainya habis.</p>
                    </div>

                    <div>
                        <x-input-label for="purchase_price" value="Purchase Price (Rp)" />
                        <x-text-input id="purchase_price" name="purchase_price" type="number" min="0" class="mt-1 block w-full" placeholder="Total harga beli" required />
                    </div>

                    <div>
                        <x-input-label for="salvage_value" value="Salvage Value (Rp)" />
                        <x-text-input id="salvage_value" name="salvage_value" type="number" min="0" value="0" class="mt-1 block w-full" placeholder="Nilai sisa di akhir umur" required />
                        <p class="text-xs text-gray-500 mt-1">Nilai sisa aset di akhir umur ekonomisnya (bisa 0).</p>
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="accumulated_depreciation" value="Penyusutan Berjalan / Accumulated Depreciation (Opsional)" />
                        <x-text-input id="accumulated_depreciation" name="accumulated_depreciation" type="number" min="0" value="0" class="mt-1 block w-full" placeholder="Total penyusutan yang sudah terjadi" />
                        <p class="text-xs text-amber-600 mt-1"><strong>Khusus untuk aset lama/existing:</strong> Masukkan total penyusutan yang sudah terjadi sejak tanggal beli hingga hari ini. Jika ini adalah aset baru, biarkan 0.</p>
                    </div>
                </div>
            </div>

            <div class="glass bg-white dark:bg-gray-800 rounded-[2rem] p-8 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Accounting Setup</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <x-input-label for="asset_account_id" value="Asset Account (Debit)" />
                        <select name="asset_account_id" id="asset_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Select Asset Account (e.g. 1210 - Peralatan Kantor)...</option>
                            @foreach($accounts as $account)
                                @if($account->type === 'asset')
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="depreciation_account_id" value="Depreciation Expense Account" />
                        <select name="depreciation_account_id" id="depreciation_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Select Expense Account (e.g. 6xxx)...</option>
                            @foreach($accounts as $account)
                                @if($account->type === 'expense')
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="accumulated_account_id" value="Accumulated Depreciation Account" />
                        <select name="accumulated_account_id" id="accumulated_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Select Contra-Asset Account...</option>
                            @foreach($accounts as $account)
                                @if($account->type === 'asset' || $account->type === 'liability')
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('fixed-assets.index') }}" class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30">
                    Register Asset
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
