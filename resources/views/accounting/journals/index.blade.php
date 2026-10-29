<x-app-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');
        
        [x-cloak] { display: none !important; }

        .journal-container {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background-color: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.06);
        }
        
        .dark .glass-card {
            background-color: rgba(17, 24, 39, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.25);
        }

        /* Type Badges */
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-bill { background: rgba(99, 102, 241, 0.12); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.25); }
        .badge-pay { background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); }
        .badge-trx { background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25); }
        .badge-jv { background: rgba(168, 85, 247, 0.12); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.25); }
        .badge-orphaned { background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25); }

        /* Floating Bulk Action Bar */
        .bulk-bar {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 60;
        }

        .bulk-bar-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(24px);
        }

        .dark .bulk-bar-inner {
            background: rgba(30, 41, 59, 0.97);
        }

        .bulk-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 1rem;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .bulk-btn:active { transform: scale(0.96); }

        .bulk-btn-del { background: rgba(244, 63, 94, 0.2); color: #fb7185; }
        .bulk-btn-del:hover { background: #f43f5e; color: #fff; }

        /* Custom checkbox */
        .bulk-check {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 2px solid #d1d5db;
            cursor: pointer;
            accent-color: #6366f1;
            transition: all 0.15s;
        }
        .dark .bulk-check {
            border-color: #4b5563;
        }

        /* Selected row highlight */
        .row-selected {
            background-color: rgba(99, 102, 241, 0.08) !important;
        }
        .dark .row-selected {
            background-color: rgba(99, 102, 241, 0.15) !important;
        }

        @keyframes slideUpIn {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        .animate-slide-up {
            animation: slideUpIn 0.25s ease-out forwards;
        }
    </style>

    <div class="journal-container py-8 px-4 sm:px-6 lg:px-8"
         x-data="{ 
            selectedIds: [],
            selectAll: false,
            toggleAll() {
                if (this.selectAll) {
                    this.selectedIds = Array.from(document.querySelectorAll('.journal-checkbox')).map(el => parseInt(el.value));
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
                if (!this.selectedIds || this.selectedIds.length === 0) return;
                if (confirm('Hapus ' + this.selectedIds.length + ' jurnal yang dipilih secara permanen?')) {
                    window.__submitBulkAction(actionUrl, this.selectedIds);
                }
            }
         }"
         x-init="$watch('selectedIds', val => { 
             const checkboxes = document.querySelectorAll('.journal-checkbox');
             selectAll = checkboxes.length > 0 && val.length === checkboxes.length; 
         })">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        General <span class="text-indigo-600">Ledger</span>
                    </h2>
                    <span class="px-3 py-1 text-xs font-black uppercase tracking-wider rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">
                        Jurnal Umum
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                    Pencatatan akuntansi otomatis (Invoice, Pembayaran, Bank) & manual entry.
                </p>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.journals.create') }}" class="inline-flex items-center px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-600/30 transition-all transform hover:-translate-y-0.5 active:scale-95">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Jurnal Baru
                </a>
            </div>
        </div>

        <!-- Orphaned Journals Notification Banner -->
        @if($orphanedCount > 0 && request('type') !== 'orphaned')
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-800/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                        Ditemukan {{ $orphanedCount }} Jurnal Piutang Tertinggal (Orphaned)
                    </h4>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                        Jurnal ini berasal dari invoice yang sebelumnya telah dihapus tetapi jurnal penagihannya masih tersisa di buku besar.
                    </p>
                </div>
            </div>
            <a href="{{ route('accounting.journals.index', ['type' => 'orphaned']) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm shrink-0 flex items-center gap-1.5">
                <span>Filter & Bersihkan</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
        @endif

        <!-- Advanced Filter & Search Card -->
        <div class="glass-card rounded-[2rem] p-6 mb-8 border border-gray-200/50 dark:border-gray-700/50 shadow-sm relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

            <form method="GET" action="{{ route('accounting.journals.index') }}" class="relative z-10 space-y-4">
                <!-- Baris 1: Pencarian Kode Billing & Nama Billing -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Kode Billing / No. Referensi
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                            </span>
                            <input type="text" name="billing_code" value="{{ request('billing_code') }}" placeholder="Contoh: INV-202610..., BILL-..." 
                                class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Nama Billing / Pelanggan
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </span>
                            <input type="text" name="billing_name" value="{{ request('billing_name') }}" placeholder="Nama pelanggan pada deskripsi billing..." 
                                class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Pencarian Umum (Deskripsi / Akun)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi, memo, kode/nama akun..." 
                                class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Filter Tipe, Akun, Tanggal, Per Page -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 pt-2">
                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Tipe Jurnal
                        </label>
                        <select name="type" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                            <option value="all" {{ request('type') == 'all' || !request('type') ? 'selected' : '' }}>Semua Tipe</option>
                            <option value="BILL" {{ request('type') == 'BILL' ? 'selected' : '' }}>📄 Tagihan Pelanggan (BILL-)</option>
                            <option value="PAY" {{ request('type') == 'PAY' ? 'selected' : '' }}>💰 Pembayaran Invoice (PAY-)</option>
                            <option value="TRX" {{ request('type') == 'TRX' ? 'selected' : '' }}>🏦 Transaksi Bank (TRX-)</option>
                            <option value="JV" {{ request('type') == 'JV' ? 'selected' : '' }}>📝 Jurnal Manual (JV-)</option>
                            <option value="orphaned" {{ request('type') == 'orphaned' ? 'selected' : '' }}>⚠️ Invoice Terhapus (Orphaned)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Filter Akun
                        </label>
                        <select name="account_id" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                            <option value="">Semua Akun</option>
                            @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} - {{ $acc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Dari Tanggal
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                            class="w-full py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                            class="w-full py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Per Halaman
                        </label>
                        <select name="per_page" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900/60 text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                            <option value="20" {{ request('per_page', 25) == 20 ? 'selected' : '' }}>20 baris</option>
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25 baris</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                            <option value="250" {{ request('per_page') == 250 ? 'selected' : '' }}>250 baris</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Tombol Submit & Reset -->
                <div class="flex flex-wrap items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800 gap-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">
                        Menampilkan <span class="font-bold text-gray-900 dark:text-white">{{ $journals->total() }}</span> jurnal ditemukan
                    </div>

                    <div class="flex items-center gap-2">
                        @if(request()->anyFilled(['search', 'billing_code', 'billing_name', 'type', 'account_id', 'date_from', 'date_to']) && request('type') !== 'all')
                            <a href="{{ route('accounting.journals.index') }}" 
                                class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                                Reset Filter
                            </a>
                        @endif
                        <button type="submit" 
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/70 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700">
                            <th class="px-5 py-4 w-12 text-center">
                                <input type="checkbox" class="bulk-check" x-model="selectAll" @change="toggleAll" title="Pilih Semua di halaman ini">
                            </th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Referensi & Tipe</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Deskripsi Billing</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Rincian Akun (Debit / Kredit)</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Debit</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Kredit</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                        @forelse($journals as $journal)
                        @php
                            $ref = $journal->reference ?? '';
                            $isBill = str_starts_with($ref, 'BILL-');
                            $isPay = str_starts_with($ref, 'PAY-');
                            $isTrx = str_starts_with($ref, 'TRX-');
                            $isJv = str_starts_with($ref, 'JV-');
                            
                            // Check if this BILL- has an invoice that no longer exists
                            $isOrphaned = false;
                            if ($isBill) {
                                $invNum = substr($ref, 5);
                                $isOrphaned = !\App\Models\Invoice::where('invoice_number', $invNum)->exists();
                            }
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition-colors align-top"
                            :class="{ 'row-selected': selectedIds.includes({{ $journal->id }}) }">
                            <!-- Checkbox Column -->
                            <td class="px-5 py-4 text-center">
                                <input type="checkbox" 
                                       value="{{ $journal->id }}" 
                                       class="bulk-check journal-checkbox" 
                                       :checked="selectedIds.includes({{ $journal->id }})"
                                       @change="toggleOne({{ $journal->id }})">
                            </td>

                            <!-- Tanggal -->
                            <td class="px-5 py-4 whitespace-nowrap text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($journal->date)->format('d/m/Y') }}
                            </td>

                            <!-- Referensi & Badge Tipe -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <a href="{{ route('accounting.journals.show', $journal) }}" class="group">
                                        <span class="font-mono text-xs font-bold bg-gray-100 dark:bg-gray-700/80 px-2 py-0.5 rounded text-gray-900 dark:text-white group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 group-hover:text-indigo-600 transition-colors">
                                            {{ $journal->reference }}
                                        </span>
                                    </a>

                                    <div class="flex items-center gap-1 mt-0.5">
                                        @if($isBill)
                                            <span class="badge-type badge-bill">Billing</span>
                                            @if($isOrphaned)
                                                <span class="badge-type badge-orphaned" title="Invoice asli telah dihapus dari database">⚠️ Terhapus</span>
                                            @endif
                                        @elseif($isPay)
                                            <span class="badge-type badge-pay">Bayar</span>
                                        @elseif($isTrx)
                                            <span class="badge-type badge-trx">Bank</span>
                                        @elseif($isJv)
                                            <span class="badge-type badge-jv">Manual</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Deskripsi & Input By -->
                            <td class="px-5 py-4 text-xs text-gray-700 dark:text-gray-300">
                                <div class="font-medium max-w-md break-words">{{ $journal->description }}</div>
                                <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <span>Input: {{ $journal->creator->name ?? 'System' }}</span>
                                    <span>•</span>
                                    <span>{{ $journal->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </td>

                            <!-- Detail Akun Jurnal (Debit / Kredit) -->
                            <td class="px-5 py-4">
                                <div class="space-y-1 max-w-sm">
                                    @foreach($journal->items as $item)
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="{{ $item->credit > 0 ? 'ml-4 text-gray-500 dark:text-gray-400' : 'font-semibold text-gray-800 dark:text-gray-200' }}">
                                            <span class="font-mono text-[10px] text-gray-400">{{ $item->account->code ?? '-' }}</span> {{ $item->account->name ?? 'N/A' }}
                                        </span>
                                        <span class="font-mono text-[11px] {{ $item->debit > 0 ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-emerald-600 dark:text-emerald-400 font-bold' }}">
                                            {{ $item->debit > 0 ? 'Dr' : 'Cr' }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Total Debit -->
                            <td class="px-5 py-4 text-right font-mono text-xs font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                Rp {{ number_format($journal->items->sum('debit'), 0, ',', '.') }}
                            </td>

                            <!-- Total Kredit -->
                            <td class="px-5 py-4 text-right font-mono text-xs font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                Rp {{ number_format($journal->items->sum('credit'), 0, ',', '.') }}
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="flex justify-center items-center space-x-1.5">
                                    <a href="{{ route('accounting.journals.show', $journal) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Lihat Detail Jurnal">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('accounting.journals.destroy', $journal) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurnal {{ $journal->reference }} ini? Tindakan ini tidak dapat dibatalkan.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors cursor-pointer" title="Hapus Jurnal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Tidak ada jurnal ditemukan</p>
                                    <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci atau filter pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($journals->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                {{ $journals->links() }}
            </div>
            @endif
        </div>

        <!-- Floating Bulk Action Bar (muncul saat jurnal dipilih) -->
        <div class="bulk-bar animate-slide-up" x-show="selectedIds.length > 0" x-cloak x-transition>
            <div class="bulk-bar-inner">
                <!-- Counter terpilih -->
                <div style="display:flex;align-items:center;gap:8px;padding-right:12px;border-right:1px solid rgba(255,255,255,0.15);">
                    <div style="width:30px;height:30px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <span style="color:#fff;font-size:12px;font-weight:900;" x-text="selectedIds.length"></span>
                    </div>
                    <span style="color:rgba(255,255,255,0.7);font-size:12px;font-weight:700;">jurnal dipilih</span>
                </div>

                <!-- Tombol Hapus Terpilih -->
                @if(auth()->user()->isAdmin())
                <button type="button" 
                        @click="submitBulkAction('{{ route('accounting.journals.bulk-destroy') }}')" 
                        class="bulk-btn bulk-btn-del">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus Terpilih
                </button>
                @endif

                <!-- Batal Pilih -->
                <button type="button" 
                        @click="selectedIds = []; selectAll = false" 
                        style="padding:6px 10px;border-radius:10px;color:rgba(255,255,255,0.5);background:transparent;border:none;cursor:pointer;font-size:11px;font-weight:700;transition:all 0.15s;" 
                        onmouseover="this.style.color='#fff';this.style.background='rgba(255,255,255,0.1)'" 
                        onmouseout="this.style.color='rgba(255,255,255,0.5)';this.style.background='transparent'" 
                        title="Batal pilih semua">
                    Batal
                </button>
            </div>
        </div>

    </div>

    <!-- Script Form Submission Helper -->
    <script>
        window.__submitBulkAction = function(actionUrl, selectedIds) {
            if (!selectedIds || selectedIds.length === 0) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            form.style.display = 'none';

            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = tokenMeta ? tokenMeta.content : '{{ csrf_token() }}';
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
    </script>
</x-app-layout>
