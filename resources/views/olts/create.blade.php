<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('olts.index') }}" class="btn-ghost btn-icon" aria-label="Kembali"><x-icon name="chevron-right" class="w-5 h-5 rotate-180" /></a>
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Tambah OLT</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">Daftarkan perangkat OLT untuk dipantau lewat SNMP.</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('olts.store') }}" method="POST" class="max-w-4xl mx-auto space-y-6">
        @csrf
        @include('olts._form', ['olt' => null])
        <div class="flex justify-end gap-3">
            <a href="{{ route('olts.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" class="w-4 h-4" /> Simpan OLT</button>
        </div>
    </form>
</x-app-layout>
