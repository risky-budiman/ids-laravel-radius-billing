<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Tambah Master Pajak') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                <form action="{{ route('accounting.taxes.store') }}" method="POST" class="p-8 space-y-6">
                    @csrf
                    
                    <div>
                        <x-input-label value="Nama Pajak (Contoh: PPN 12%)" />
                        <x-text-input name="name" class="mt-1 w-full" required placeholder="e.g. PPN Jasa Internet" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label value="Kode Pajak" />
                            <x-text-input name="code" class="mt-1 w-full uppercase" required placeholder="PPN-12" />
                        </div>
                        <div>
                            <x-input-label value="Tarif Persentase (%)" />
                            <x-text-input name="rate" type="number" step="0.01" class="mt-1 w-full" required placeholder="12" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Akun Akuntansi Terkait" />
                        <select name="chart_of_account_id" required class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl shadow-sm text-sm">
                            <option value="">Pilih Akun</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-2 italic">Gunakan akun "2103 - Hutang Pajak (PPN)" untuk pajak keluaran.</p>
                    </div>

                    <div class="flex justify-end pt-4 space-x-3">
                        <a href="{{ route('accounting.taxes.index') }}" class="px-6 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">Batal</a>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all">Simpan Pajak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
