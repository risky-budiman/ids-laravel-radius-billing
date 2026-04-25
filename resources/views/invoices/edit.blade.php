<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit Invoice') }}: {{ $invoice->invoice_number }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="p-8">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2" 
                    id="customer_billing_info"
                    data-type="{{ $invoice->customer->billing_type }}"
                    data-method="{{ $invoice->customer->billing_method }}"
                    data-next="{{ $invoice->customer->billing_next_date ? $invoice->customer->billing_next_date->format('Y-m-d') : '' }}"
                    data-expired="{{ $invoice->customer->expired_at ? $invoice->customer->expired_at->format('Y-m-d') : '' }}"
                    data-price="{{ $invoice->customer->package->price ?? 0 }}"
                >
                    <x-input-label for="customer_name" :value="__('Subscriber')" />
                    <x-text-input id="customer_name" type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-700 cursor-not-allowed" value="{{ $invoice->customer->name }} ({{ $invoice->customer->username }}) - [{{ strtoupper($invoice->customer->billing_type) }} / {{ strtoupper($invoice->customer->billing_method) }}]" disabled />
                </div>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="billing_duration" :value="__('Pilihan Durasi')" />
                        <select id="billing_duration" name="billing_period" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            @for ($i = 1; $i <= 12; $i++)
                                @php $val = $i . " Month" . ($i > 1 ? "s" : ""); @endphp
                                <option value="{{ $val }}" data-months="{{ $i }}" {{ $invoice->billing_period == $val ? 'selected' : '' }}>
                                    {{ $val }}
                                </option>
                            @endfor
                            <option value="Custom" data-months="1" {{ !preg_match('/^\d+ Month/', $invoice->billing_period) ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="period_start" :value="__('Period Start')" />
                        <x-text-input id="period_start" name="period_start" type="date" class="mt-1 block w-full" value="{{ $invoice->period_start ? $invoice->period_start->format('Y-m-d') : '' }}" required />
                    </div>
                    <div>
                        <x-input-label for="period_end" :value="__('Period End')" />
                        <x-text-input id="period_end" name="period_end" type="date" class="mt-1 block w-full" value="{{ $invoice->period_end ? $invoice->period_end->format('Y-m-d') : '' }}" required />
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const billingInfo = document.getElementById('customer_billing_info');
                        const durationSelect = document.getElementById('billing_duration');
                        const startInput = document.getElementById('period_start');
                        const endInput = document.getElementById('period_end');
                        const amountInput = document.getElementById('amount');

                        const billingType = billingInfo.getAttribute('data-type');
                        const billingMethod = billingInfo.getAttribute('data-method');
                        const nextDateStr = billingInfo.getAttribute('data-next');
                        const expiredDateStr = billingInfo.getAttribute('data-expired');
                        const basePrice = parseFloat(billingInfo.getAttribute('data-price')) || 0;

                        function updatePeriodStart() {
                            // Only update if current value is empty
                            if (startInput.value) return;

                            let startDate = new Date();
                            const dueDateValue = document.getElementById('due_date').value;

                            if (billingType === 'postpaid' && dueDateValue) {
                                // If Postpaid, Period is the month BEFORE the Due Date month
                                startDate = new Date(dueDateValue);
                                startDate.setMonth(startDate.getMonth() - 1);
                                startDate.setDate(1);
                            } else if (billingMethod === 'cycle') {
                                startDate.setDate(1); 
                            } else if (billingMethod === 'renewal' && expiredDateStr) {
                                const expDate = new Date(expiredDateStr);
                                if (expDate > new Date()) startDate = expDate;
                            } else if (nextDateStr) {
                                startDate = new Date(nextDateStr);
                            }

                            const year = startDate.getFullYear();
                            const month = String(startDate.getMonth() + 1).padStart(2, '0');
                            const day = String(startDate.getDate()).padStart(2, '0');
                            startInput.value = `${year}-${month}-${day}`;
                            
                            updatePeriodEnd();
                        }

                        function updatePeriodEnd() {
                            if (durationSelect.value === 'Custom') return;
                            
                            const startDate = new Date(startInput.value);
                            if (isNaN(startDate.getTime())) return;

                            const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                            const endDate = new Date(startDate);
                            endDate.setMonth(endDate.getMonth() + months);
                            
                            // Subtract 1 day so it ends at the end of the month
                            endDate.setDate(endDate.getDate() - 1);
                            
                            const year = endDate.getFullYear();
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const day = String(endDate.getDate()).padStart(2, '0');
                            endInput.value = `${year}-${month}-${day}`;
                        }

                        function updateAmount() {
                            const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                            if (basePrice > 0) {
                                amountInput.value = basePrice * months;
                            }
                        }

                        durationSelect.addEventListener('change', () => {
                            updatePeriodEnd();
                            updateAmount();
                        });
                        startInput.addEventListener('change', updatePeriodEnd);

                        // Initial trigger
                        if (!startInput.value) {
                            updatePeriodStart();
                        } else {
                            updatePeriodEnd();
                            updateAmount();
                        }
                    });
                </script>
                <div>
                    <x-input-label for="amount" :value="__('Amount (Rp)')" />
                    <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" value="{{ $invoice->amount }}" required />
                </div>
                <div>
                    <x-input-label for="due_date" :value="__('Due Date')" />
                    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" value="{{ $invoice->due_date->format('Y-m-d') }}" required />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="notes" :value="__('Additional Info (Keterangan)')" />
                    <textarea id="notes" name="notes" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="3" placeholder="Contoh: Biaya Instalasi, Denda, dll">{{ $invoice->notes }}</textarea>
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('invoices.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Update Invoice</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
