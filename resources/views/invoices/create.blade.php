<x-app-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
        
        .font-outfit { font-family: 'Outfit', sans-serif; }

        /* Custom Select Arrow Fix */
        .select-custom {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236366f1' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.25rem center;
            background-size: 1.25rem;
            padding-right: 3rem;
            appearance: none;
        }

        /* Date Picker Reset */
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            @apply dark:invert opacity-60;
        }
    </style>

    <div class="font-outfit min-h-screen bg-gray-50 dark:bg-gray-950 transition-colors duration-300 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            
            <!-- Modern Header -->
            <div class="flex items-center space-x-6 mb-12">
                <div class="w-16 h-16 bg-indigo-600 rounded-[2rem] flex items-center justify-center shadow-2xl shadow-indigo-600/20">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h2 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight">Generate Invoice</h2>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-1">Manual billing creation for subscribers.</p>
                </div>
            </div>

            <form action="{{ route('invoices.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                @csrf
                
                <div class="lg:col-span-8 space-y-8">
                    <!-- Subscriber Selection -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 md:p-10 border border-gray-100 dark:border-gray-800 shadow-sm">
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 px-2">1. Target Subscriber</label>
                        <select id="customer_id" name="customer_id" class="select-custom w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required>
                            <option value="">-- Choose Subscriber --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
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
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 md:p-10 border border-gray-100 dark:border-gray-800 shadow-sm">
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6 px-2">2. Billing Period</label>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-[9px] font-bold text-indigo-500 uppercase mb-2 px-1">Duration</label>
                                <select id="billing_duration" name="billing_period" class="select-custom w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all">
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
                                <input id="period_start" name="period_start" type="date" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required />
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">End Date</label>
                                <input id="period_end" name="period_end" type="date" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" required />
                            </div>
                        </div>

                        <div class="mt-8 pt-8 border-t border-gray-50 dark:border-gray-800">
                            <label class="block text-[10px] font-black text-rose-500 uppercase tracking-widest mb-4 px-2">3. Settlement Due Date</label>
                            <div class="max-w-xs">
                                <input id="due_date" name="due_date" type="date" class="w-full bg-rose-50/30 dark:bg-rose-900/10 border-none rounded-2xl py-4 px-6 text-sm font-bold text-rose-600 dark:text-rose-400 focus:ring-4 focus:ring-rose-500/10 transition-all" required />
                                <p class="mt-3 text-[9px] text-gray-400 font-bold uppercase tracking-widest px-2 italic">Automatically synced with subscriber cycle</p>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 md:p-10 border border-gray-100 dark:border-gray-800 shadow-sm">
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-6 px-2">4. Financial Adjustments</label>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">Base Amount (Rp)</label>
                                <input id="amount" name="amount" type="number" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="0" required />
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase mb-2 px-1">Tax Scheme</label>
                                <select id="tax_id" name="tax_id" class="select-custom w-full bg-gray-50 dark:bg-gray-800 border-none rounded-2xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                    <option value="">No Tax Applied</option>
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 px-2">5. Notes & Keterangan</label>
                        <textarea id="notes" name="notes" rows="4" class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-3xl py-4 px-6 text-sm font-bold text-gray-900 dark:text-gray-100 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="Enter invoice details..."></textarea>
                    </div>
                </div>

                <!-- Right Audit Panel -->
                <div class="lg:col-span-4 sticky top-12">
                    <div class="bg-indigo-50/50 dark:bg-indigo-900/20 rounded-[3rem] p-10 relative overflow-hidden border border-indigo-100 dark:border-indigo-500/10 backdrop-blur-md">
                        <div class="flex items-center justify-between mb-10">
                            <span class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.3em]">Auditor</span>
                            <div class="p-2 bg-indigo-600 rounded-xl text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>

                        <div class="text-center mb-10">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 block">Total Payable</span>
                            <div class="flex items-start justify-center">
                                <span class="text-lg font-black mt-2 mr-1 text-indigo-500 italic">Rp</span>
                                <span id="total_display" class="text-6xl font-black tracking-tighter text-gray-900 dark:text-white">0</span>
                            </div>
                        </div>

                        <div class="space-y-4 pt-10 border-t border-indigo-100 dark:border-indigo-500/10 mb-10">
                            <div class="flex justify-between items-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                <span>Subtotal</span>
                                <span id="subtotal_side" class="text-gray-900 dark:text-white">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                <span>Tax</span>
                                <span id="tax_side" class="text-gray-900 dark:text-white">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-2xl transition-all shadow-xl shadow-indigo-600/20 active:scale-95 uppercase tracking-widest text-[10px]">
                            Publish Invoice
                        </button>
                        
                        <a href="{{ route('invoices.index') }}" class="block text-center mt-6 text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                            Discard & Exit
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
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
