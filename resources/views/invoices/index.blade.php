<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Invoices') }}
            </h2>
            <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Generate Invoice
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400 font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-100/80 border border-red-200 text-red-700 rounded-xl font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
        <form action="{{ route('invoices.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-[3] w-full min-w-[300px]">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Invoice atau Nama..." 
                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
            <div class="w-full md:w-32">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Status</label>
                <select name="status" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    <option value="">Semua</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-md shadow-indigo-600/20">
                    Filter
                </button>
                <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl text-sm font-bold hover:bg-gray-200 transition-all text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" 
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
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subscriber</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $invoices->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">
                                <a href="{{ route('invoices.edit', $invoice) }}" class="hover:underline">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invoice->billing_period ?? '-' }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">
                                    @if($invoice->period_start && $invoice->period_end)
                                        {{ $invoice->period_start->format('d M Y') }} - {{ $invoice->period_end->format('d M Y') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100">
                                {{ $invoice->customer->name ?? 'Unknown' }}
                                <div class="text-xs text-gray-500">{{ $invoice->customer->username ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 italic max-w-xs truncate">
                                {{ $invoice->notes ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ $invoice->due_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center space-x-3">
                                    @if($invoice->status == 'unpaid')
                                        <div class="flex items-center space-x-2">
                                            <!-- Online Payment Button (Direct to Signed Portal) -->
                                            <a href="{{ URL::signedRoute('portal.invoice', ['invoice' => $invoice->id]) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all shadow-md shadow-indigo-600/30 transform hover:-translate-y-0.5 active:translate-y-0">
                                                Online
                                            </a>

                                            <!-- Manual Payment Button (Triggers Modal) -->
                                            <button type="button" @click="openPaymentModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', '{{ number_format($invoice->amount, 2, ',', '.') }}')" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-colors border border-emerald-100">
                                                Pay
                                            </button>

                                            <!-- WhatsApp Button -->
                                            <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all shadow-sm shadow-emerald-500/20 flex items-center" title="Send WhatsApp Notification">
                                                    Send WA
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($invoice->status == 'paid')
                                        <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="cancel_payment" value="1">
                                            <button type="submit" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300 text-xs font-bold uppercase tracking-wider" onclick="return confirm('Cancel this payment and revert to unpaid?');">Cancel</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Delete this invoice?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-xs font-bold uppercase tracking-wider">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No Invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
            {{ $invoices->links() }}
        </div>

        <!-- Payment Modal -->
        <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-cloak>
            <div @click.away="showPaymentModal = false" class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-gray-100 dark:border-gray-700">
                <div class="bg-emerald-600 px-6 py-4 text-white flex justify-between items-center">
                    <h3 class="font-bold text-lg">Catat Pembayaran Manual</h3>
                    <button @click="showPaymentModal = false" class="text-emerald-100 hover:text-white">&times;</button>
                </div>
                
                <form :action="'/invoices/' + activeInvoiceId" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="mark_as_paid" value="1">
                    
                    <div class="mb-6">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">No. Invoice</label>
                        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="activeInvoiceNumber"></p>
                        
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Tagihan</label>
                            <p class="text-2xl font-black text-gray-900 dark:text-white" x-text="'Rp ' + activeInvoiceAmount"></p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Pilih Rekening Penerima</label>
                        <select name="bank_account_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-emerald-500 focus:border-emerald-500 transition-all" required>
                            <option value="">-- Pilih Rekening --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[10px] text-gray-500 italic">Uang akan otomatis dicatat sebagai pemasukan pada rekening yang dipilih.</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-2xl shadow-lg shadow-emerald-600/30 transition-all transform hover:-translate-y-0.5">
                            Konfirmasi Pembayaran
                        </button>
                        <button type="button" @click="showPaymentModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-bold py-3 rounded-2xl transition-all">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
