<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2>Invoice & Penagihan</h2>
                <p class="page-subtitle">Kelola tagihan pelanggan, pembayaran, dan notifikasi WhatsApp.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('invoices.generate-automated') }}" method="POST" onsubmit="return confirm('Proses penagihan otomatis untuk semua pelanggan aktif?')">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <x-icon name="refresh" class="w-4 h-4" /> Generate Massal
                    </button>
                </form>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-create-invoice'))" class="btn-primary">
                    <x-icon name="plus" class="w-4 h-4" /> Invoice Baru
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        /* Checkbox & baris terpilih */
        .bulk-check { width: 16px; height: 16px; border-radius: 4px; cursor: pointer; accent-color: #4f46e5; }
        .row-selected { background-color: rgb(238 242 255 / 0.7) !important; }
        .dark .row-selected { background-color: rgb(99 102 241 / 0.12) !important; }

        /* Bar aksi massal (melayang di bawah) */
        .bulk-bar { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); z-index: 60; }
        .bulk-bar-inner {
            display: flex; align-items: center; gap: 6px; padding: 8px 8px 8px 14px; border-radius: 0.875rem;
            background: #0f172a; border: 1px solid rgb(255 255 255 / 0.08); box-shadow: 0 20px 40px -16px rgb(15 23 42 / 0.5);
        }
        .bulk-btn {
            display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border-radius: 0.5rem;
            font-size: 13px; font-weight: 500; color: #e2e8f0; background: transparent; border: none; cursor: pointer;
            transition: background-color .15s, color .15s; white-space: nowrap;
        }
        .bulk-btn:hover { background: rgb(255 255 255 / 0.08); color: #fff; }
        .bulk-btn-pay svg { color: #34d399; }
        .bulk-btn-wa svg { color: #4ade80; }
        .bulk-btn-del { color: #fda4af; }
        .bulk-btn-del:hover { background: rgb(244 63 94 / 0.15); color: #fecdd3; }
        @keyframes slideUpIn { from { opacity: 0; transform: translateX(-50%) translateY(12px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
        .animate-slide-up { animation: slideUpIn .2s ease-out forwards; }
    </style>

    <div
         x-data="{ 
            showCreateModal: false,
            showPaymentModal: false, 
            showBulkPayModal: false,
            activeInvoiceId: null, 
            activeInvoiceNumber: '',
            activeInvoiceAmount: '',
            selectedIds: [],
            selectAll: false,
            openPaymentModal(id, number, amount) {
                this.activeInvoiceId = id;
                this.activeInvoiceNumber = number;
                this.activeInvoiceAmount = amount;
                this.showPaymentModal = true;
            },
            toggleAll() {
                if (this.selectAll) {
                    this.selectedIds = Array.from(document.querySelectorAll('.inv-checkbox')).map(el => parseInt(el.value));
                } else {
                    this.selectedIds = [];
                }
            },
            toggleOne(id) {
                const idx = this.selectedIds.indexOf(id);
                if (idx > -1) {
                    this.selectedIds.splice(idx, 1);
                } else {
                    this.selectedIds.push(id);
                }
            },
            submitBulkAction(actionUrl) {
                window.__submitBulkAction(actionUrl, this.selectedIds);
            }
         }"
         x-init="$watch('selectedIds', val => { selectAll = val.length === document.querySelectorAll('.inv-checkbox').length && val.length > 0; })"
         @open-create-invoice.window="showCreateModal = true">

        @if(session('success'))
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3">
                <x-icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Filter --}}
        <div class="card mb-6">
            <form action="{{ route('invoices.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end p-4">
                <div class="md:col-span-5">
                    <label for="f-search" class="label">Cari</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-surface-400">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <input id="f-search" type="text" name="search" value="{{ request('search') }}" placeholder="Nomor invoice atau nama pelanggan" class="input pl-9">
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label for="f-period" class="label">Periode</label>
                    <input id="f-period" type="month" name="period" value="{{ request('period') }}" class="input">
                </div>
                <div class="md:col-span-2">
                    <label for="f-status" class="label">Status</label>
                    <select id="f-status" name="status" class="input">
                        <option value="">Semua status</option>
                        <option value="paid" @selected(request('status') == 'paid')>Lunas</option>
                        <option value="unpaid" @selected(request('status') == 'unpaid')>Belum bayar</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="btn-primary flex-1 !h-10">Terapkan</button>
                    <a href="{{ route('invoices.index') }}" class="btn-secondary btn-icon !h-10 !w-10" title="Reset filter" aria-label="Reset filter">
                        <x-icon name="x" class="w-4 h-4" />
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel invoice --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-surface-200 dark:border-surface-800">
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    @if($invoices->total() > 0)
                        Menampilkan <span class="font-medium text-surface-900 dark:text-white">{{ $invoices->firstItem() }}–{{ $invoices->lastItem() }}</span> dari <span class="font-medium text-surface-900 dark:text-white">{{ number_format($invoices->total(), 0, ',', '.') }}</span> invoice
                    @else
                        Tidak ada invoice
                    @endif
                </p>
                <p class="text-xs text-surface-400" x-show="selectedIds.length > 0" x-cloak><span x-text="selectedIds.length"></span> dipilih</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table whitespace-nowrap min-w-[960px]">
                    <thead>
                        <tr>
                            <th class="w-10 !pr-0"><input type="checkbox" class="bulk-check" x-model="selectAll" @change="toggleAll()" aria-label="Pilih semua"></th>
                            <th class="w-12 text-center">#</th>
                            <th>Invoice</th>
                            <th>Pelanggan</th>
                            <th class="text-right">Jumlah</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php $isOverdue = $invoice->status !== 'paid' && $invoice->due_date && $invoice->due_date->isPast(); @endphp
                            <tr :class="selectedIds.includes({{ $invoice->id }}) && 'row-selected'">
                                <td class="!pr-0">
                                    <input type="checkbox" class="bulk-check inv-checkbox" value="{{ $invoice->id }}" :checked="selectedIds.includes({{ $invoice->id }})" @change="toggleOne({{ $invoice->id }})" aria-label="Pilih {{ $invoice->invoice_number }}">
                                </td>
                                <td class="text-center text-surface-400 tabular-nums">{{ $invoices->firstItem() + $loop->index }}</td>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-surface-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">{{ $invoice->invoice_number }}</a>
                                    <p class="text-xs mt-0.5 {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-surface-400' }}">
                                        Jatuh tempo {{ $invoice->due_date?->locale('id')->translatedFormat('d M Y') ?? '—' }}{{ $isOverdue ? ' · terlambat' : '' }}
                                    </p>
                                </td>
                                <td>
                                    <p class="font-medium text-surface-900 dark:text-white">{{ $invoice->customer->name ?? 'Tidak diketahui' }}</p>
                                    <p class="text-xs text-surface-400 font-mono">{{ $invoice->customer->username ?? '' }}</p>
                                </td>
                                <td class="text-right">
                                    <p class="font-medium tabular-nums text-surface-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
                                    <p class="text-xs text-surface-400">{{ $invoice->billing_period ?? 'Biaya layanan' }}</p>
                                </td>
                                <td>
                                    @if($invoice->status == 'paid')
                                        <span class="badge-success">Lunas</span>
                                    @elseif($isOverdue)
                                        <span class="badge-danger">Terlambat</span>
                                    @else
                                        <span class="badge-warning">Belum bayar</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-0.5">
                                        <a href="{{ route('invoices.show', $invoice) }}" target="_blank" class="btn-ghost btn-icon !h-8 !w-8" title="Lihat & cetak" aria-label="Lihat & cetak">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        @if($invoice->status == 'unpaid')
                                            <a href="{{ URL::signedRoute('portal.invoice', ['invoice' => $invoice->id]) }}" target="_blank" class="btn-ghost btn-icon !h-8 !w-8" title="Buka portal pembayaran" aria-label="Buka portal pembayaran">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                            <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-ghost btn-icon !h-8 !w-8 !text-emerald-600 dark:!text-emerald-400" title="Kirim tagihan via WhatsApp" aria-label="Kirim WhatsApp">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                </button>
                                            </form>
                                            <button type="button" @click="openPaymentModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', '{{ number_format($invoice->amount, 0, ',', '.') }}')" class="btn-secondary btn-sm !h-8 ml-1">
                                                Tandai lunas
                                            </button>
                                        @else
                                            <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="inline">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="cancel_payment" value="1">
                                                <button type="submit" onclick="return confirm('Batalkan pembayaran dan kembalikan status ke BELUM BAYAR?');" class="btn-ghost btn-icon !h-8 !w-8 hover:!text-amber-600" title="Batalkan pembayaran" aria-label="Batalkan pembayaran">
                                                    <x-icon name="refresh" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Hapus invoice {{ $invoice->invoice_number }} secara permanen?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-ghost btn-icon !h-8 !w-8 hover:!text-rose-600 hover:!bg-rose-50 dark:hover:!bg-rose-500/10" title="Hapus" aria-label="Hapus invoice">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="!py-16 text-center">
                                    <div class="mx-auto w-10 h-10 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400 mb-3">
                                        <x-icon name="document" class="w-5 h-5" />
                                    </div>
                                    <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Tidak ada invoice</p>
                                    <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Coba ubah filter atau buat invoice baru.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="px-5 py-3 border-t border-surface-200 dark:border-surface-800">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>


        <!-- Floating Bulk Action Bar (OUTSIDE overflow-hidden, but INSIDE x-data) -->
        <div class="bulk-bar animate-slide-up" x-show="selectedIds.length > 0" x-cloak x-transition>
            <div class="bulk-bar-inner">
                <!-- Counter -->
                <div style="display:flex;align-items:center;gap:8px;padding-right:12px;border-right:1px solid rgba(255,255,255,0.1);">
                    <div style="width:32px;height:32px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <span style="color:#fff;font-size:12px;font-weight:900;" x-text="selectedIds.length"></span>
                    </div>
                    <span style="color:rgba(255,255,255,0.6);font-size:12px;font-weight:700;">dipilih</span>
                </div>

                <!-- Mark Paid -->
                <button type="button" @click="showBulkPayModal = true" class="bulk-btn bulk-btn-pay">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Bayar
                </button>

                <!-- Send WhatsApp -->
                <button type="button" @click="if(confirm('Kirim notifikasi WhatsApp ke ' + selectedIds.length + ' invoice terpilih?')) submitBulkAction('{{ route('invoices.bulk-whatsapp') }}')" class="bulk-btn bulk-btn-wa">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    WhatsApp
                </button>

                <!-- Delete -->
                <button type="button" @click="if(confirm('Hapus ' + selectedIds.length + ' invoice yang dipilih secara permanen?')) submitBulkAction('{{ route('invoices.bulk-delete') }}')" class="bulk-btn bulk-btn-del">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>

                <!-- Deselect All -->
                <button type="button" @click="selectedIds = []; selectAll = false" 
                        style="margin-left:4px;padding:8px;border-radius:10px;color:rgba(255,255,255,0.4);background:transparent;border:none;cursor:pointer;transition:all 0.15s;" 
                        onmouseover="this.style.color='#fff';this.style.background='rgba(255,255,255,0.1)'" 
                        onmouseout="this.style.color='rgba(255,255,255,0.4)';this.style.background='transparent'" 
                        title="Batal pilih">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Payment Modal (Glass Style) -->
        <div x-show="showPaymentModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showPaymentModal = false" class="glass-card rounded-lg w-full max-w-lg overflow-hidden relative border border-gray-200 dark:border-gray-700 shadow-elevated">
                <div class="bg-emerald-600 px-6 py-6 text-white relative">
                    <h3 class="font-bold text-xl tracking-tight">Manual Payment</h3>
                    <p class="text-emerald-100 text-xs font-bold uppercase tracking-wide mt-1 opacity-80">Recording external transaction</p>
                    <button @click="showPaymentModal = false" class="absolute top-8 right-8 text-white/50 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form :action="'/admin/invoices/' + activeInvoiceId" method="POST" class="p-8">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="mark_as_paid" value="1">
                    
                    <div class="mb-8 grid grid-cols-2 gap-4">
                        <div class="p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                            <label class="block text-xs font-semibold text-indigo-500 mb-1">Invoice ID</label>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="activeInvoiceNumber"></p>
                        </div>
                        <div class="p-5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                            <label class="block text-xs font-semibold text-emerald-500 mb-1">Total Due</label>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'Rp ' + activeInvoiceAmount"></p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-xs font-semibold text-gray-400 mb-3">Receiving Bank Account</label>
                        <select name="bank_account_id" class="w-full py-2.5 rounded-lg border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                            <option value="">Select Account...</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-3 text-xs text-gray-400 font-medium italic px-2 leading-relaxed">
                            <span class="text-emerald-500 font-semibold mr-1">NOTE:</span>
                            Payment will be automatically logged to the general ledger and the customer service will be reactivated if currently suspended.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-xl shadow-sm transition-all transform">
                            CONFIRM PAYMENT
                        </button>
                        <button type="button" @click="showPaymentModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-4 rounded-xl transition-all">
                            CANCEL
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Payment Modal -->
        <div x-show="showBulkPayModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showBulkPayModal = false" class="glass-card rounded-lg w-full max-w-lg overflow-hidden relative border border-gray-200 dark:border-gray-700 shadow-elevated">
                <div class="bg-emerald-600 px-6 py-6 text-white relative">
                    <h3 class="font-bold text-xl tracking-tight">Bulk Payment</h3>
                    <p class="text-emerald-100 text-xs font-bold uppercase tracking-wide mt-1 opacity-80">
                        Marking <span x-text="selectedIds.length" class="text-white"></span> invoices as paid
                    </p>
                    <button @click="showBulkPayModal = false" class="absolute top-8 right-8 text-white/50 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form action="{{ route('invoices.bulk-mark-paid') }}" method="POST" class="p-8">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    
                    <div class="mb-8">
                        <div class="p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-100 dark:border-indigo-800/30 mb-5">
                            <label class="block text-xs font-semibold text-indigo-500 mb-1">Total Invoice Dipilih</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedIds.length + ' invoice (hanya yang berstatus unpaid akan diproses)'"></p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-xs font-semibold text-gray-400 mb-3">Rekening Penerima</label>
                        <select name="bank_account_id" class="w-full py-2.5 rounded-lg border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                            <option value="">Pilih Rekening...</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-3 text-xs text-gray-400 font-medium italic px-2 leading-relaxed">
                            <span class="text-emerald-500 font-semibold mr-1">NOTE:</span>
                            Semua invoice terpilih yang berstatus unpaid akan ditandai lunas, dicatat ke kas/bank, dan layanan pelanggan akan direaktivasi.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-4 rounded-xl shadow-sm transition-all transform">
                            KONFIRMASI PEMBAYARAN MASSAL
                        </button>
                        <button type="button" @click="showBulkPayModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-4 rounded-xl transition-all">
                            BATAL
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Create Invoice Modal -->
        <div x-show="showCreateModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showCreateModal = false" class="rounded-xl w-full max-w-6xl overflow-hidden relative border border-slate-200 dark:border-slate-800 shadow-elevated max-h-[90vh] flex flex-col bg-slate-100 dark:bg-slate-950 backdrop-blur-2xl">
                
                <!-- Modal Header -->
                <div class="px-8 py-6 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-indigo-600 dark:bg-indigo-500 rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white tracking-tight">Generate Invoice</h2>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">Billing Engine</span>
                            </div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">Manual billing creation for active subscribers.</p>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" type="button" class="text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full p-2.5 hover:rotate-90 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <!-- Modal Body (Scrollable) -->
                <div class="overflow-y-auto p-6 sm:p-8" style="max-height: calc(90vh - 95px);">
                    <form action="{{ route('invoices.store') }}" method="POST" id="createInvoiceForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        @csrf
                        
                        <div class="lg:col-span-8 space-y-6">
                            <!-- 1. Subscriber Selection -->
                            <div class="bg-white dark:bg-slate-900 rounded-xl p-7 border border-slate-200 dark:border-slate-800 shadow-sm">
                                <div class="flex items-center space-x-2.5 mb-4">
                                    <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                    <label class="text-xs font-semibold text-slate-900 dark:text-white uppercase tracking-wide">1. Target Subscriber</label>
                                </div>
                                <select id="customer_id" name="customer_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-4 px-5 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all" required>
                                    <option value="" class="dark:bg-slate-900 dark:text-white">-- Select Subscriber --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" class="dark:bg-slate-900 dark:text-white" {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                                            data-price="{{ $customer->package->price ?? 0 }}"
                                            data-type="{{ $customer->billing_type }}"
                                            data-method="{{ $customer->billing_method }}"
                                            data-next="{{ $customer->billing_next_date ? $customer->billing_next_date->format('Y-m-d') : '' }}"
                                            data-due="{{ $customer->billing_due_date ? $customer->billing_due_date->format('Y-m-d') : '' }}"
                                            data-expired="{{ $customer->expired_at ? $customer->expired_at->format('Y-m-d') : '' }}"
                                        >
                                            {{ $customer->name }} &bull; ({{ $customer->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. Period & Duration -->
                            <div class="bg-white dark:bg-slate-900 rounded-xl p-7 border border-slate-200 dark:border-slate-800 shadow-sm">
                                <div class="flex items-center space-x-2.5 mb-6">
                                    <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                    <label class="text-xs font-semibold text-slate-900 dark:text-white uppercase tracking-wide">2. Billing Period & Dates</label>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 px-1">Duration</label>
                                        <select id="billing_duration" name="billing_period" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }} Month{{ $i > 1 ? 's' : '' }}" class="dark:bg-slate-900 dark:text-white" data-months="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>
                                                    {{ $i }} Month{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                            <option value="Custom" class="dark:bg-slate-900 dark:text-white">Custom Range</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 px-1">Start Date</label>
                                        <input id="period_start" name="period_start" value="{{ old('period_start') }}" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 px-1">End Date</label>
                                        <input id="period_end" name="period_end" value="{{ old('period_end') }}" type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 px-4 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all" required />
                                    </div>
                                </div>

                                <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center space-x-2.5 mb-3">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        <label class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wide">Settlement Due Date</label>
                                    </div>
                                    <div class="max-w-xs">
                                        <input id="due_date" name="due_date" value="{{ old('due_date') }}" type="date" class="w-full bg-rose-50/60 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 rounded-lg py-3.5 px-5 text-sm font-bold text-rose-700 dark:text-rose-300 focus:ring-2 focus:ring-rose-500/30 focus:border-rose-500 transition-all" required />
                                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500 font-medium px-1">Synced automatically with subscriber cycle.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Financial Adjustments -->
                            <div class="bg-white dark:bg-slate-900 rounded-xl p-7 border border-slate-200 dark:border-slate-800 shadow-sm">
                                <div class="flex items-center space-x-2.5 mb-6">
                                    <span class="w-2 h-2 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                    <label class="text-xs font-semibold text-slate-900 dark:text-white uppercase tracking-wide">3. Financial Adjustments</label>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 px-1">Base Amount (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-3.5 font-bold text-sm text-slate-400 dark:text-slate-500">Rp</span>
                                            <input id="amount" name="amount" value="{{ old('amount') }}" type="number" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 pl-11 pr-5 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all" placeholder="0" required />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 px-1">Tax Scheme</label>
                                        <select id="tax_id" name="tax_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 px-5 text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                                            <option value="" class="dark:bg-slate-900 dark:text-white">No Tax Applied</option>
                                            @foreach($taxes as $tax)
                                                <option value="{{ $tax->id }}" class="dark:bg-slate-900 dark:text-white" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2.5 mb-2.5">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    <label class="text-xs font-semibold text-slate-900 dark:text-white uppercase tracking-wide">Notes & Remarks</label>
                                </div>
                                <textarea id="notes" name="notes" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-3.5 px-5 text-sm font-medium text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all" placeholder="Enter optional notes for customer...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <!-- Right Panel: Auditor Summary Panel (Fully readable Light & Dark) -->
                        <div class="lg:col-span-4 sticky top-0">
                            <div class="bg-indigo-600 dark:bg-slate-900 text-white rounded-xl p-7 relative overflow-hidden border border-indigo-500/30 dark:border-slate-800 shadow-elevated">
                                <!-- Subtle Background Glow -->
                                <div class="absolute -top-12 -right-12 w-44 h-44 bg-white/10 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

                                <div class="flex items-center justify-between mb-7 relative z-10">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-xs font-semibold text-indigo-100 dark:text-indigo-300">Audit Summary</span>
                                    </div>
                                    <div class="p-2 bg-white/15 dark:bg-slate-800 rounded-xl text-white dark:text-indigo-400 border border-white/20 dark:border-slate-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                </div>

                                <div class="text-center mb-7 relative z-10">
                                    <span class="text-xs font-bold text-indigo-200 dark:text-slate-400 mb-1.5 block">Total Payable</span>
                                    <div class="flex items-baseline justify-center">
                                        <span class="text-lg font-semibold mr-1 text-indigo-200 dark:text-indigo-400 italic">Rp</span>
                                        <span id="total_display" class="text-4xl font-semibold tracking-tight text-white font-mono drop-shadow">0</span>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-5 border-t border-white/20 dark:border-slate-800 mb-7 relative z-10">
                                    <div class="flex justify-between items-center text-xs font-semibold text-indigo-100 dark:text-slate-300">
                                        <span class="text-indigo-200 dark:text-slate-400">Subtotal</span>
                                        <span id="subtotal_side" class="text-white font-mono">Rp 0</span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs font-semibold text-indigo-100 dark:text-slate-300">
                                        <span class="text-indigo-200 dark:text-slate-400">Tax</span>
                                        <span id="tax_side" class="text-emerald-300 dark:text-emerald-400 font-mono">Rp 0</span>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-white dark:bg-indigo-600 hover:bg-slate-100 dark:hover:bg-indigo-500 text-indigo-900 dark:text-white font-semibold py-2.5 rounded-lg transition-all shadow-sm  uppercase tracking-wide text-xs relative z-10 flex items-center justify-center space-x-2 group">
                                    <span>Publish Invoice</span>
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </button>
                                
                                <button type="button" @click="showCreateModal = false" class="w-full block text-center mt-3 text-xs font-bold text-indigo-200 dark:text-slate-400 hover:text-white transition-colors py-2 relative z-10">
                                    Discard & Exit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        window.__submitBulkAction = function(actionUrl, selectedIds) {
            if (!selectedIds || selectedIds.length === 0) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            form.style.display = 'none';

            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(tokenInput);

            selectedIds.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                form.appendChild(inp);
            });

            document.body.appendChild(form);
            form.submit();
        };

        // Live calculation logic for the New Invoice modal
        document.addEventListener('DOMContentLoaded', function() {
            const customerSelect = document.getElementById('customer_id');
            const durationSelect = document.getElementById('billing_duration');
            const startInput = document.getElementById('period_start');
            const endInput = document.getElementById('period_end');
            const amountInput = document.getElementById('amount');
            const taxSelect = document.getElementById('tax_id');
            const totalDisplay = document.getElementById('total_display');
            const subtotalSide = document.getElementById('subtotal_side');
            const taxSide = document.getElementById('tax_side');
            const dueDateInput = document.getElementById('due_date');

            if (!customerSelect) return; // Prevent errors if fields don't exist

            function updateAll() {
                const selectedOption = customerSelect.options[customerSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) return;

                const billingType = selectedOption.getAttribute('data-type');
                const billingMethod = selectedOption.getAttribute('data-method');
                const nextDateStr = selectedOption.getAttribute('data-next');
                const expiredDateStr = selectedOption.getAttribute('data-expired');
                const dueDateStr = selectedOption.getAttribute('data-due');
                
                let startDate = new Date();
                
                if (billingType === 'postpaid' && dueDateStr) {
                    startDate = new Date(dueDateStr);
                    startDate.setMonth(startDate.getMonth() - 1);
                    startDate.setDate(1);
                } else if (billingMethod === 'cycle') {
                    if (billingType === 'postpaid') {
                        startDate.setDate(1);
                    } else {
                        if (nextDateStr) startDate = new Date(nextDateStr);
                        else startDate.setDate(1);
                    }
                } else if (billingMethod === 'renewal' && expiredDateStr) {
                    const expDate = new Date(expiredDateStr);
                    if (expDate > new Date()) startDate = expDate;
                } else if (nextDateStr) {
                    startDate = new Date(nextDateStr);
                }

                if (!startInput.value) {
                    startInput.value = startDate.toISOString().split('T')[0];
                }

                if (durationSelect.value !== 'Custom') {
                    const sDate = new Date(startInput.value);
                    const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                    const eDate = new Date(sDate);
                    eDate.setMonth(eDate.getMonth() + months);
                    eDate.setDate(eDate.getDate() - 1);
                    endInput.value = eDate.toISOString().split('T')[0];
                }

                const basePrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                amountInput.value = basePrice * months;

                if (dueDateStr) {
                    dueDateInput.value = dueDateStr;
                }

                calculateTotal();
            }

            function calculateTotal() {
                const subtotal = parseFloat(amountInput.value) || 0;
                const taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex]?.getAttribute('data-rate')) || 0;
                const taxAmount = (subtotal * taxRate) / 100;
                const total = subtotal + taxAmount;
                
                const formatter = new Intl.NumberFormat('id-ID');
                totalDisplay.textContent = formatter.format(total);
                subtotalSide.textContent = 'Rp ' + formatter.format(subtotal);
                taxSide.textContent = 'Rp ' + formatter.format(taxAmount);
            }

            customerSelect.addEventListener('change', updateAll);
            durationSelect.addEventListener('change', updateAll);
            startInput.addEventListener('change', updateAll);
            amountInput.addEventListener('input', calculateTotal);
            taxSelect.addEventListener('change', calculateTotal);

            if (customerSelect.value) updateAll();
        });
    </script>
</x-app-layout>
