<x-app-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
        
        /* Prevent Alpine.js FOUC */
        [x-cloak] { display: none !important; }

        .invoice-container {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        
        .dark .glass-card {
            background-color: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        /* Status Pills - pure CSS */
        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border: 1px solid;
        }

        .status-paid {
            background-color: #ecfdf5;
            color: #059669;
            border-color: #d1fae5;
        }
        .dark .status-paid {
            background-color: rgba(6, 78, 59, 0.2);
            color: #34d399;
            border-color: rgba(6, 78, 59, 0.3);
        }

        .status-unpaid {
            background-color: #fff1f2;
            color: #e11d48;
            border-color: #ffe4e6;
        }
        .dark .status-unpaid {
            background-color: rgba(136, 19, 55, 0.2);
            color: #fb7185;
            border-color: rgba(136, 19, 55, 0.3);
        }

        /* Bulk Action Bar */
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
            gap: 10px;
            padding: 14px 24px;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255,255,255,0.08);
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
            padding: 8px 16px;
            border-radius: 1rem;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .bulk-btn:active { transform: scale(0.95); }

        .bulk-btn-pay { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .bulk-btn-pay:hover { background: #10b981; color: #fff; }

        .bulk-btn-wa { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .bulk-btn-wa:hover { background: #22c55e; color: #fff; }

        .bulk-btn-del { background: rgba(244, 63, 94, 0.2); color: #fb7185; }
        .bulk-btn-del:hover { background: #f43f5e; color: #fff; }

        /* Custom checkbox */
        .bulk-check {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            accent-color: #4f46e5;
            transition: all 0.15s;
        }
        .dark .bulk-check {
            border-color: #4b5563;
        }

        @keyframes slideUpIn {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        .animate-slide-up {
            animation: slideUpIn 0.3s ease-out forwards;
        }

        /* Selected row highlight */
        .row-selected {
            background-color: rgba(99, 102, 241, 0.06) !important;
        }
        .dark .row-selected {
            background-color: rgba(99, 102, 241, 0.12) !important;
        }
    </style>

    <div class="invoice-container py-8 px-4 sm:px-6 lg:px-8"
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
         x-init="$watch('selectedIds', val => { selectAll = val.length === document.querySelectorAll('.inv-checkbox').length && val.length > 0; })">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
            <div>
                <h2 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                    Billing <span class="text-indigo-600">&</span> Invoices
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-medium">Manage subscriber billing, payments, and notifications.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <form action="{{ route('invoices.generate-automated') }}" method="POST" onsubmit="return confirm('Proses penagihan otomatis untuk semua pelanggan aktif?')">
                    @csrf
                    <button type="submit" class="group flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all active:scale-95">
                        <svg class="w-5 h-5 mr-2 text-indigo-500 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Batch Generate
                    </button>
                </form>
                <button type="button" @click="showCreateModal = true" class="flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-indigo-600/30 transition-all transform hover:-translate-y-0.5 active:scale-95">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    New Invoice
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-8 flex items-center p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30 rounded-2xl animate-fade-in">
                <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-emerald-800 dark:text-emerald-400">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Advanced Filters -->
        <div class="glass-card rounded-2xl px-5 py-6 mb-8 overflow-hidden relative border border-gray-200/50 dark:border-gray-700/50 shadow-sm">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
            <form action="{{ route('invoices.index') }}" method="GET" class="relative z-10 grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Search Invoice or Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ex: INV-12345 or John Doe..." 
                            class="w-full pl-11 pr-4 py-3 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white/50">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Filter by Period</label>
                    <input type="month" name="period" value="{{ request('period') }}" 
                        class="w-full py-3 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white/50">
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Filter by Status</label>
                    <select name="status" class="w-full py-3 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white/50">
                        <option value="">All Statuses</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="flex-1 bg-gray-900 dark:bg-indigo-600 text-white font-bold py-3 rounded-2xl hover:shadow-lg transition-all active:scale-95">
                        Apply Filter
                    </button>
                    <a href="{{ route('invoices.index') }}" class="p-3 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-2xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all" title="Reset Filters">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </a>
                </div>
            </form>
        </div>

        <!-- Invoices Table Container -->
        <div class="glass-card rounded-2xl overflow-hidden border border-gray-200/50 dark:border-gray-700/50 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap" style="min-width: 1000px;">
                    <thead>
                        <tr class="bg-gray-50/30 dark:bg-gray-800/30">
                            <th class="pl-4 pr-6 py-5 w-12">
                                <input type="checkbox" class="bulk-check" x-model="selectAll" @change="toggleAll()">
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center w-16">#</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Invoice Details</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Subscriber</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Total Amount</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] w-28">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center w-56">Action Console</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors group" :class="selectedIds.includes({{ $invoice->id }}) && 'row-selected'">
                                <td class="pl-4 pr-6 py-5">
                                    <input type="checkbox" class="bulk-check inv-checkbox" value="{{ $invoice->id }}" :checked="selectedIds.includes({{ $invoice->id }})" @change="toggleOne({{ $invoice->id }})">
                                </td>
                                <td class="px-6 py-5 text-xs font-black text-gray-300 dark:text-gray-600 text-center">{{ $invoices->firstItem() + $loop->index }}</td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div>
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-black text-gray-900 dark:text-white hover:text-indigo-600 transition-colors">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">
                                                Due {{ $invoice->due_date->format('d M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm font-black text-gray-900 dark:text-white">{{ $invoice->customer->name ?? 'Unknown' }}</div>
                                    <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mt-0.5">@ {{ $invoice->customer->username ?? '' }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm font-black text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold mt-1">{{ $invoice->billing_period ?? 'Service Fee' }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="status-pill {{ $invoice->status == 'paid' ? 'status-paid' : 'status-unpaid' }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-center space-x-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                        <!-- Print Invoice (Always visible) -->
                                        <a href="{{ route('invoices.show', $invoice) }}" target="_blank" 
                                           class="p-2.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-600 hover:text-white transition-all active:scale-90" title="View & Print">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>

                                        @if($invoice->status == 'unpaid')

                                            <!-- Portal Link -->
                                            <a href="{{ URL::signedRoute('portal.invoice', ['invoice' => $invoice->id]) }}" target="_blank" 
                                               class="p-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl hover:bg-indigo-600 hover:text-white transition-all active:scale-90" title="Open Portal">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>

                                            <!-- Manual Payment -->
                                            <button type="button" @click="openPaymentModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', '{{ number_format($invoice->amount, 0, ',', '.') }}')" 
                                                    class="p-2.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl hover:bg-emerald-600 hover:text-white transition-all active:scale-90" title="Mark as Paid">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                            </button>

                                            <!-- WhatsApp Notify -->
                                            <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 transition-all active:scale-90" title="Send WhatsApp Notice">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Paid State: Reset Option -->
                                            <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="inline">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="cancel_payment" value="1">
                                                <button type="submit" class="p-2.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl hover:bg-amber-600 hover:text-white transition-all" 
                                                        onclick="return confirm('Reset payment status to UNPAID?');" title="Cancel Payment">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete this invoice?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2.5 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-xl hover:bg-rose-600 hover:text-white transition-all" title="Delete Permanent">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px]">No invoices matching filters found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($invoices->hasPages())
            <div class="px-8 py-6 bg-gray-50/30 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-700">
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
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showPaymentModal = false" class="glass-card rounded-2xl w-full max-w-lg overflow-hidden relative border border-gray-200 dark:border-gray-700 shadow-2xl">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-6 text-white relative">
                    <h3 class="font-bold text-xl tracking-tight">Manual Payment</h3>
                    <p class="text-emerald-100 text-xs font-bold uppercase tracking-widest mt-1 opacity-80">Recording external transaction</p>
                    <button @click="showPaymentModal = false" class="absolute top-8 right-8 text-white/50 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form :action="'/admin/invoices/' + activeInvoiceId" method="POST" class="p-8">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="mark_as_paid" value="1">
                    
                    <div class="mb-8 grid grid-cols-2 gap-4">
                        <div class="p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl border border-indigo-100 dark:border-indigo-800/30">
                            <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Invoice ID</label>
                            <p class="text-sm font-black text-gray-900 dark:text-white" x-text="activeInvoiceNumber"></p>
                        </div>
                        <div class="p-5 bg-emerald-50 dark:bg-emerald-900/30 rounded-3xl border border-emerald-100 dark:border-emerald-800/30">
                            <label class="block text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Total Due</label>
                            <p class="text-sm font-black text-gray-900 dark:text-white" x-text="'Rp ' + activeInvoiceAmount"></p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Receiving Bank Account</label>
                        <select name="bank_account_id" class="w-full py-4 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                            <option value="">Select Account...</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-3 text-[10px] text-gray-400 font-medium italic px-2 leading-relaxed">
                            <span class="text-emerald-500 font-black mr-1">NOTE:</span>
                            Payment will be automatically logged to the general ledger and the customer service will be reactivated if currently suspended.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-3xl shadow-xl shadow-emerald-600/30 transition-all transform hover:-translate-y-1 active:scale-95">
                            CONFIRM PAYMENT
                        </button>
                        <button type="button" @click="showPaymentModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-4 rounded-3xl transition-all">
                            CANCEL
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Payment Modal -->
        <div x-show="showBulkPayModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showBulkPayModal = false" class="glass-card rounded-2xl w-full max-w-lg overflow-hidden relative border border-gray-200 dark:border-gray-700 shadow-2xl">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-6 py-6 text-white relative">
                    <h3 class="font-bold text-xl tracking-tight">Bulk Payment</h3>
                    <p class="text-emerald-100 text-xs font-bold uppercase tracking-widest mt-1 opacity-80">
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
                        <div class="p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-3xl border border-indigo-100 dark:border-indigo-800/30 mb-5">
                            <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Total Invoice Dipilih</label>
                            <p class="text-lg font-black text-gray-900 dark:text-white" x-text="selectedIds.length + ' invoice (hanya yang berstatus unpaid akan diproses)'"></p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Rekening Penerima</label>
                        <select name="bank_account_id" class="w-full py-4 rounded-2xl border-gray-100 dark:border-gray-700 dark:bg-gray-900/50 text-sm font-bold focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                            <option value="">Pilih Rekening...</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-3 text-[10px] text-gray-400 font-medium italic px-2 leading-relaxed">
                            <span class="text-emerald-500 font-black mr-1">NOTE:</span>
                            Semua invoice terpilih yang berstatus unpaid akan ditandai lunas, dicatat ke kas/bank, dan layanan pelanggan akan direaktivasi.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-3xl shadow-xl shadow-emerald-600/30 transition-all transform hover:-translate-y-1 active:scale-95">
                            KONFIRMASI PEMBAYARAN MASSAL
                        </button>
                        <button type="button" @click="showBulkPayModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-4 rounded-3xl transition-all">
                            BATAL
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Create Invoice Modal -->
        <div x-show="showCreateModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak>
            <div @click.away="showCreateModal = false" class="glass-card rounded-3xl w-full max-w-6xl overflow-hidden relative border border-gray-200 dark:border-gray-700 shadow-2xl max-h-[90vh] flex flex-col bg-gray-50 dark:bg-gray-950">
                
                <!-- Modal Header -->
                <div class="px-8 py-6 flex items-center justify-between border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-indigo-600 rounded-[1.5rem] flex items-center justify-center shadow-lg shadow-indigo-600/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Generate Invoice</h2>
                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1">Manual billing creation for subscribers.</p>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors bg-gray-100 dark:bg-gray-800 rounded-full p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <!-- Modal Body (Scrollable) -->
                <div class="overflow-y-auto p-4 sm:p-8" style="max-height: calc(90vh - 100px);">
                    <form action="{{ route('invoices.store') }}" method="POST" id="createInvoiceForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        @csrf
                        
                        <div class="lg:col-span-8 space-y-8">
                            <!-- Subscriber Selection -->
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-800 shadow-sm">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 px-2">1. Target Subscriber</label>
                                <select id="customer_id" name="customer_id" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required>
                                    <option value="">-- Choose Subscriber --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                                            data-price="{{ $customer->package->price ?? 0 }}"
                                            data-type="{{ $customer->billing_type }}"
                                            data-method="{{ $customer->billing_method }}"
                                            data-next="{{ $customer->billing_next_date ? $customer->billing_next_date->format('Y-m-d') : '' }}"
                                            data-due="{{ $customer->billing_due_date ? $customer->billing_due_date->format('Y-m-d') : '' }}"
                                            data-expired="{{ $customer->expired_at ? $customer->expired_at->format('Y-m-d') : '' }}"
                                        >
                                            {{ $customer->name }} ({{ $customer->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Period & Duration -->
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-800 shadow-sm">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6 px-2">2. Billing Period</label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-[9px] font-bold text-indigo-500 uppercase mb-2 px-1">Duration</label>
                                        <select id="billing_duration" name="billing_period" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                            @for ($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }} Month{{ $i > 1 ? 's' : '' }}" data-months="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>
                                                    {{ $i }} Month{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                            <option value="Custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">Start Date</label>
                                        <input id="period_start" name="period_start" value="{{ old('period_start') }}" type="date" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required />
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">End Date</label>
                                        <input id="period_end" name="period_end" value="{{ old('period_end') }}" type="date" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required />
                                    </div>
                                </div>

                                <div class="mt-8 pt-8 border-t border-gray-50 dark:border-gray-800">
                                    <label class="block text-[10px] font-black text-rose-500 uppercase tracking-widest mb-4 px-2">3. Settlement Due Date</label>
                                    <div class="max-w-xs">
                                        <input id="due_date" name="due_date" value="{{ old('due_date') }}" type="date" class="w-full bg-rose-50/30 dark:bg-rose-900/10 border-none rounded-2xl py-4 px-6 text-sm font-bold text-rose-600 dark:text-rose-400 focus:ring-4 focus:ring-rose-500/10 transition-all" required />
                                        <p class="mt-3 text-[9px] text-gray-400 font-bold uppercase tracking-widest px-2 italic">Automatically synced with subscriber cycle</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Details -->
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 border border-gray-100 dark:border-gray-800 shadow-sm">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6 px-2">4. Financial Adjustments</label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">Base Amount (Rp)</label>
                                        <input id="amount" name="amount" value="{{ old('amount') }}" type="number" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="0" required />
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">Tax Scheme</label>
                                        <select id="tax_id" name="tax_id" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                            <option value="">No Tax Applied</option>
                                            @foreach($taxes as $tax)
                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 px-2">5. Notes & Keterangan</label>
                                <textarea id="notes" name="notes" rows="4" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-3xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="Enter invoice details...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <!-- Right Audit Panel -->
                        <div class="lg:col-span-4 sticky top-0">
                            <div class="bg-indigo-50/50 dark:bg-indigo-900/20 rounded-[3rem] p-8 relative overflow-hidden border border-indigo-100 dark:border-indigo-500/10 backdrop-blur-md">
                                <div class="flex items-center justify-between mb-8">
                                    <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em]">Auditor</span>
                                    <div class="p-2 bg-indigo-600 rounded-xl text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                </div>

                                <div class="text-center mb-8">
                                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 block">Total Payable</span>
                                    <div class="flex items-start justify-center">
                                        <span class="text-lg font-black mt-2 mr-1 text-indigo-500 italic">Rp</span>
                                        <span id="total_display" class="text-5xl font-black tracking-tighter text-gray-900 dark:text-white">0</span>
                                    </div>
                                </div>

                                <div class="space-y-4 pt-8 border-t border-indigo-100 dark:border-indigo-500/10 mb-8">
                                    <div class="flex justify-between items-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                        <span>Subtotal</span>
                                        <span id="subtotal_side" class="text-gray-900 dark:text-white">Rp 0</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                        <span>Tax</span>
                                        <span id="tax_side" class="text-gray-900 dark:text-white">Rp 0</span>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition-all shadow-xl shadow-indigo-600/20 active:scale-95 uppercase tracking-widest text-[10px]">
                                    Publish Invoice
                                </button>
                                
                                <button type="button" @click="showCreateModal = false" class="w-full block text-center mt-4 text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest transition-colors py-4">
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
