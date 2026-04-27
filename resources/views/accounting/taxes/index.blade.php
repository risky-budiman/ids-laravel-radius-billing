<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Master Data Pajak') }}
            </h2>
            <a href="{{ route('accounting.taxes.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20 transition-all">
                + Tambah Pajak
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-3xl border border-gray-100 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Nama Pajak</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Kode</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Tarif (%)</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Akun Akuntansi</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @forelse($taxes as $tax)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ $tax->name }}</div>
                                            @if($tax->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-tighter">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-mono text-sm text-gray-500">{{ $tax->code }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-lg font-black text-indigo-600">{{ number_format($tax->rate, 0) }}%</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                                {{ $tax->chartOfAccount->code }} - {{ $tax->chartOfAccount->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <a href="{{ route('accounting.taxes.edit', $tax) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-widest">Edit</a>
                                            <form action="{{ route('accounting.taxes.destroy', $tax) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-widest" onclick="return confirm('Hapus pajak ini?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada data pajak.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
