<x-app-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
        
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

        .status-pill {
            @apply px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border;
        }

        .status-paid {
            @apply bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/30;
        }

        .status-unpaid {
            @apply bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/30;
        }
    </style>

    <div class="invoice-container py-8 px-4 sm:px-6 lg:px-8">
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
                <a href="{{ route('invoices.create') }}" class="flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-indigo-600/30 transition-all transform hover:-translate-y-0.5 active:scale-95">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    New Invoice
                </a>
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
        <div class="glass-card rounded-[2.5rem] p-8 mb-10 overflow-hidden relative">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl"></div>
            <form action="{{ route('invoices.index') }}" method="GET" class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
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
        <div class="glass-card rounded-[2.5rem] overflow-hidden" 
             x-data="{ 
                showPaymentModal: false, 
                activeInvoiceId: null, 
                activeInvoiceNumber: '',
                activeInvoiceAmount: '',
                openPaymentModal(id, number, amount) {
                    this.activeInvoiceId = id;
                    this.activeInvoiceNumber = number;
                    this.activeInvoiceAmount = amount;
                    this.showPaymentModal = true;
                }
             }">
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap border-collapse">
                    <thead>
                        <tr class="bg-gray-50/30 dark:bg-gray-800/30">
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">#</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Invoice Details</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Subscriber</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Total Amount</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-center">Action Console</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors group">
                                <td class="px-8 py-6 text-xs font-black text-gray-300 dark:text-gray-600">{{ $invoices->firstItem() + $loop->index }}</td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div>
                                            <a href="{{ route('invoices.edit', $invoice) }}" class="text-sm font-black text-gray-900 dark:text-white hover:text-indigo-600 transition-colors">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">
                                                Due {{ $invoice->due_date->format('d M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-gray-900 dark:text-white">{{ $invoice->customer->name ?? 'Unknown' }}</div>
                                    <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mt-0.5">@ {{ $invoice->customer->username ?? '' }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-black text-gray-900 dark:text-white">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold mt-1">{{ $invoice->billing_period ?? 'Service Fee' }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="status-pill {{ $invoice->status == 'paid' ? 'status-paid' : 'status-unpaid' }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center justify-center space-x-2 opacity-60 group-hover:opacity-100 transition-opacity">
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
                                <td colspan="6" class="px-8 py-20 text-center">
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
            <div @click.away="showPaymentModal = false" class="glass-card rounded-[3rem] w-full max-w-lg overflow-hidden relative border-white/20">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 px-8 py-8 text-white relative">
                    <h3 class="font-black text-2xl tracking-tight">Manual Payment</h3>
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
    </div>
</x-app-layout>
