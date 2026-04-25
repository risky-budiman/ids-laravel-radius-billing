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

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subscriber</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $invoices->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ $invoice->invoice_number }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100">
                                {{ $invoice->customer->name ?? 'Unknown' }}
                                <div class="text-xs text-gray-500">{{ $invoice->customer->username ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                @if($invoice->status == 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ $invoice->due_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    @if($invoice->status == 'unpaid')
                                        <div class="flex items-center space-x-2">
                                            @if($activeGateways->count() > 1)
                                                <!-- Dropdown for multiple gateways -->
                                                <div class="relative group">
                                                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-extrabold transition-all shadow-md shadow-indigo-600/30 flex items-center">
                                                        Pay Online
                                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                                    </button>
                                                    <div class="absolute z-20 hidden group-hover:block left-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-600 p-2 transform origin-top-left transition-all">
                                                        @foreach($activeGateways as $gw)
                                                            <a href="{{ route('invoices.pay', ['invoice' => $invoice, 'gateway' => $gw->provider]) }}" target="_blank" class="block px-4 py-2 text-xs font-bold text-gray-800 dark:text-gray-200 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 rounded-lg transition-all mb-1 last:mb-0">
                                                                Pay via {{ ucfirst($gw->provider) }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @elseif($activeGateways->count() == 1)
                                                <!-- Single gateway direct button -->
                                                <a href="{{ route('invoices.pay', ['invoice' => $invoice, 'gateway' => $activeGateways->first()->provider]) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-extrabold transition-all shadow-md shadow-indigo-600/30 transform hover:-translate-y-0.5 active:translate-y-0">
                                                    Pay Online
                                                </a>
                                            @endif

                                            <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="mark_as_paid" value="1">
                                                <button type="submit" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-600 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">Mark Paid</button>
                                            </form>

                                            <!-- WhatsApp Button -->
                                            <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-xl transition-all shadow-sm shadow-green-500/20" title="Send WhatsApp Notification">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.082 19.163c-1.81 0-3.534-.446-5.112-1.267l-5.666 1.48 1.508-5.494c-.908-1.625-1.401-3.475-1.401-5.359 0-6.178 5.039-11.206 11.234-11.206 5.864 0 10.952 4.675 10.952 11.215 0 6.182-5.043 11.21-11.235 11.231z"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif($invoice->status == 'paid')
                                        <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="cancel_payment" value="1">
                                            <button type="submit" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300 text-sm font-medium" onclick="return confirm('Cancel this payment and revert to unpaid?');">Cancel Payment</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('Delete this invoice?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No Invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
            {{ $invoices->links() }}
        </div>
    </div>
</x-app-layout>
