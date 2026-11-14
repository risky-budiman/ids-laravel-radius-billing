@props([
    'title',
    'subtitle' => null,
    'closed' => false,
    'printable' => true,
])
{{-- Kepala laporan: judul + periode tercetak; filter & tombol hanya di layar. --}}
<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
    <div>
        <h2 class="flex flex-wrap items-center gap-2">
            {{ $title }}
            @if($closed)
                <span class="badge-success !text-[11px]" title="Periode sudah ditutup buku; transaksi terkunci">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    Periode ditutup
                </span>
            @endif
        </h2>
        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="no-print flex flex-wrap items-end gap-2">
        @isset($filters)
            {{ $filters }}
        @endisset
        @isset($actions)
            {{ $actions }}
        @endisset
        @if($printable)
            <button type="button" onclick="window.print()" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak
            </button>
        @endif
    </div>
</div>
