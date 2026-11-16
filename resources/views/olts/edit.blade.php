<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('olts.show', $olt) }}" class="btn-ghost btn-icon" aria-label="Kembali"><x-icon name="chevron-right" class="w-5 h-5 rotate-180" /></a>
            <div>
                <h2 class="font-bold text-2xl text-surface-900 dark:text-white leading-tight">Ubah OLT</h2>
                <p class="text-sm text-surface-500 dark:text-surface-400">{{ $olt->name }} · <span class="font-mono">{{ $olt->ip_address }}</span></p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if(session('error'))
            <div class="flex items-start gap-2.5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                <x-icon name="exclamation" class="w-5 h-5 shrink-0" /> {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('olts.update', $olt) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('olts._form', ['olt' => $olt])
            <div class="flex justify-end gap-3">
                <a href="{{ route('olts.show', $olt) }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary"><x-icon name="check" class="w-4 h-4" /> Simpan perubahan</button>
            </div>
        </form>

        {{-- Zona berbahaya: terpisah dari form agar tidak terklik tidak sengaja --}}
        <section class="card border-rose-200 dark:border-rose-500/30">
            <div class="card-body flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-rose-700 dark:text-rose-400">Hapus OLT</h3>
                    <p class="text-xs text-surface-500">Menghapus OLT beserta data port-nya. OLT yang masih dipakai pelanggan tidak bisa dihapus.</p>
                </div>
                <form action="{{ route('olts.destroy', $olt) }}" method="POST" onsubmit="return confirm('Hapus OLT {{ $olt->name }} secara permanen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus OLT</button>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
