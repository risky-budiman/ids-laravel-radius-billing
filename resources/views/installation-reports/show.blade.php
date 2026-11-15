@php
    $invoice = $report->snapshot['invoice'] ?? null;
    $invoiceModel = $invoice ? \App\Models\Invoice::find($invoice['id'] ?? null) : null;
    $canSeeInvoice = $invoiceModel && auth()->user()?->hasRole(['administrator', 'admin', 'kasir']);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3 no-print">
            <div>
                <h2>Berita Acara Instalasi</h2>
                <p class="page-subtitle">{{ $report->number }} · {{ $report->customer?->name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="window.print()" class="btn-secondary btn-sm">Cetak</button>
                <form method="POST" action="{{ route('installation-reports.send-link', $report) }}">
                    @csrf
                    <button class="btn-secondary btn-sm" @disabled(! $report->customer?->phone)>Kirim tautan ke WhatsApp</button>
                </form>
                @if($report->customer)
                    <a href="{{ route('customers.show', $report->customer) }}" class="btn-ghost btn-sm">Ke pelanggan</a>
                @endif
            </div>
        </div>
    </x-slot>

    @include('installation-reports._styles')

    <div class="max-w-5xl space-y-6">
        @foreach(['success' => 'emerald', 'error' => 'rose'] as $key => $tone)
            @if(session($key))
                <div class="no-print rounded-xl border border-{{ $tone }}-200 bg-{{ $tone }}-50 px-4 py-3 text-sm text-{{ $tone }}-800 dark:border-{{ $tone }}-500/30 dark:bg-{{ $tone }}-500/10 dark:text-{{ $tone }}-300">{{ session($key) }}</div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="no-print rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if($invoiceModel && $invoiceModel->status !== 'paid')
            <div class="no-print rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                Invoice instalasi <span class="font-mono">{{ $invoiceModel->invoice_number }}</span> belum dibayar.
                @if($report->customer?->activation_grace_expires_at)
                    Batas bayar {{ $report->customer->activation_grace_expires_at->locale('id')->translatedFormat('d M Y H:i') }}; lewat dari itu internet pelanggan diisolir.
                @endif
                @if($canSeeInvoice)
                    <a href="{{ route('invoices.show', $invoiceModel) }}" class="font-semibold underline">Buka invoice</a>
                @endif
            </div>
        @endif

        <div class="space-y-6">
            <div class="card"><div class="card-body">@include('installation-reports._document')</div></div>

            <div class="grid gap-4 md:grid-cols-2 no-print">
                <div class="card md:col-span-2">
                    <div class="card-header"><h3 class="card-title">Tanda tangan</h3></div>
                    <div class="card-body space-y-3 text-sm">
                        <p class="text-surface-500 dark:text-surface-400">Pelanggan bisa menandatangani langsung di perangkat ini, atau lewat tautan berikut:</p>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $report->signUrl() }}" class="input font-mono text-xs" onclick="this.select()">
                            <button type="button" class="btn-secondary btn-sm shrink-0" onclick="navigator.clipboard.writeText('{{ $report->signUrl() }}'); this.textContent='Tersalin'">Salin</button>
                        </div>
                        @if($report->isFullySigned())
                            <p class="font-medium text-emerald-700 dark:text-emerald-400">BA sudah ditandatangani kedua pihak.</p>
                        @endif
                    </div>
                </div>

                @foreach(['customer' => 'Pelanggan', 'technician' => 'Teknisi'] as $role => $label)
                    @continue($report->{$role . '_signature'})
                    <form method="POST" action="{{ route('installation-reports.sign', $report) }}" class="card">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <div class="card-header"><h3 class="card-title">Tanda tangan {{ strtolower($label) }}</h3></div>
                        <div class="card-body space-y-3">
                            @if($role === 'customer')
                                <div>
                                    <label class="label" for="signer_name">Nama penanda tangan</label>
                                    <input id="signer_name" name="signer_name" class="input" value="{{ old('signer_name', $report->customer?->name) }}" maxlength="120">
                                </div>
                            @endif
                            @include('installation-reports._pad', ['role' => $role])
                            <button class="btn-primary w-full">Simpan tanda tangan {{ strtolower($label) }}</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
