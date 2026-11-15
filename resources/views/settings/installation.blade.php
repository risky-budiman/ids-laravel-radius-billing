@php
    $v = fn ($k) => old($k, $values[$k] ?? '');
    $ba = old('activation_ba_enabled', $values['activation_ba_enabled']) == '1';
    $inv = old('installation_invoice_enabled', $values['installation_invoice_enabled']) == '1';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Pengaturan Instalasi</h2>
            <p class="page-subtitle">Berita Acara instalasi, invoice biaya pasang, dan batas waktu pembayarannya.</p>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.installation.update') }}" class="space-y-6"
              x-data="{ ba: {{ $ba ? 'true' : 'false' }}, inv: {{ $inv ? 'true' : 'false' }} }">
            @csrf

            <div class="card">
                <div class="card-header flex items-center justify-between gap-4">
                    <div>
                        <h3 class="card-title">Berita Acara (BA) Instalasi</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400">Setelah aktivasi, sistem membuat BA bernomor berisi data pelanggan, paket, perangkat & material terpasang. Pelanggan menandatangani di perangkat teknisi atau lewat tautan WhatsApp.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="hidden" name="activation_ba_enabled" value="0">
                        <input type="checkbox" name="activation_ba_enabled" value="1" x-model="ba" class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium">Aktifkan</span>
                    </label>
                </div>
                <div class="card-body" :class="ba ? '' : 'opacity-60'">
                    <label for="installation_ba_terms" class="label">Ketentuan pada BA</label>
                    <textarea id="installation_ba_terms" name="installation_ba_terms" rows="7" class="input !h-auto text-sm">{{ $v('installation_ba_terms') }}</textarea>
                    <p class="help-text">Dicetak di setiap BA baru. Kosongkan untuk kembali ke teks bawaan. BA yang sudah terbit tidak berubah.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header flex items-center justify-between gap-4">
                    <div>
                        <h3 class="card-title">Invoice biaya instalasi</h3>
                        <p class="text-xs text-surface-500 dark:text-surface-400">Setiap aktivasi dengan biaya instalasi menerbitkan invoice. Dibayar di lokasi (tunai/transfer) → langsung lunas. Belum dibayar → internet tetap aktif sampai batas bayar, lalu diisolir otomatis.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0">
                        <input type="hidden" name="installation_invoice_enabled" value="0">
                        <input type="checkbox" name="installation_invoice_enabled" value="1" x-model="inv" class="rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium">Aktifkan</span>
                    </label>
                </div>
                <div class="card-body grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="installation_grace_hours" class="label">Batas bayar (jam)</label>
                        <input id="installation_grace_hours" name="installation_grace_hours" type="number" min="0" max="720" value="{{ $v('installation_grace_hours') }}" class="input sm:max-w-[10rem]">
                        <p class="help-text">Sejak aktivasi. <span class="font-mono">24</span> = 1×24 jam. <span class="font-mono">0</span> = tidak pernah diisolir karena biaya instalasi. Juga berlaku untuk pembayaran lewat gateway bila invoice instalasi nonaktif.</p>
                    </div>
                    <div :class="inv ? '' : 'opacity-60'">
                        <label for="installation_reminder_hours" class="label">Pengingat WhatsApp (jam sebelum batas)</label>
                        <input id="installation_reminder_hours" name="installation_reminder_hours" type="number" min="0" max="48" value="{{ $v('installation_reminder_hours') }}" class="input sm:max-w-[10rem]">
                        <p class="help-text"><span class="font-mono">0</span> = tanpa pengingat. Dikirim sekali per pelanggan.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button class="btn-primary">Simpan pengaturan</button>
            </div>
        </form>

        <div class="card">
            <div class="card-header"><h3 class="card-title">BA terbaru</h3></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Nomor</th><th>Pelanggan</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recent as $r)
                            <tr>
                                <td><a href="{{ route('installation-reports.show', $r) }}" class="font-mono text-primary-600 hover:underline dark:text-primary-400">{{ $r->number }}</a></td>
                                <td>{{ $r->customer?->name ?? '—' }} <span class="text-xs text-surface-400 font-mono">{{ $r->customer?->customer_code }}</span></td>
                                <td class="whitespace-nowrap">{{ $r->created_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    @if($r->isFullySigned())
                                        <span class="badge badge-success">Lengkap</span>
                                    @elseif($r->customer_signature || $r->technician_signature)
                                        <span class="badge badge-warning">Sebagian</span>
                                    @else
                                        <span class="badge badge-gray">Belum ditandatangani</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-sm text-surface-500 py-6">Belum ada BA.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
