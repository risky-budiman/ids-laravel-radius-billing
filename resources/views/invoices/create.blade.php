<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Generate Invoice') }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('invoices.store') }}" method="POST" class="p-8">
            @csrf
            <div class="grid grid-cols-1 gap-6 mb-6">
                <div>
                    <x-input-label for="customer_id" :value="__('Select Subscriber')" />
                    <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" required>
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
                                {{ $customer->name }} ({{ $customer->username }}) - [{{ strtoupper($customer->billing_type) }} / {{ strtoupper($customer->billing_method) }}]
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="billing_duration" :value="__('Pilihan Durasi')" />
                        <select id="billing_duration" name="billing_period" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }} Month{{ $i > 1 ? 's' : '' }}" data-months="{{ $i }}" {{ $i == 1 ? 'selected' : '' }}>
                                    {{ $i }} Month{{ $i > 1 ? 's' : '' }}
                                </option>
                            @endfor
                            <option value="Custom" data-months="1">Custom Range</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="period_start" :value="__('Period Start')" />
                        <x-text-input id="period_start" name="period_start" type="date" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="period_end" :value="__('Period End')" />
                        <x-text-input id="period_end" name="period_end" type="date" class="mt-1 block w-full" required />
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const customerSelect = document.getElementById('customer_id');
                        const durationSelect = document.getElementById('billing_duration');
                        const startInput = document.getElementById('period_start');
                        const endInput = document.getElementById('period_end');
                        const amountInput = document.getElementById('amount');

                        function updatePeriodStart() {
                            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
                            if (!selectedOption || !selectedOption.value) return;

                            const billingType = selectedOption.getAttribute('data-type');
                            const billingMethod = selectedOption.getAttribute('data-method');
                            const nextDateStr = selectedOption.getAttribute('data-next');
                            const expiredDateStr = selectedOption.getAttribute('data-expired');
                            const dueDateStr = selectedOption.getAttribute('data-due');
                            
                            let startDate = new Date();
                            
                            if (billingType === 'postpaid' && dueDateStr) {
                                // If Postpaid, Period is the month BEFORE the Due Date month
                                startDate = new Date(dueDateStr);
                                startDate.setMonth(startDate.getMonth() - 1);
                                startDate.setDate(1);
                            } else if (billingMethod === 'cycle') {
                                // For Cycle, start at 1st of the month
                                if (billingType === 'postpaid') {
                                    // If we are generating manually, we usually bill for the current month usage
                                    startDate.setDate(1);
                                } else {
                                    // Prepaid Cycle usually follows the system's next date
                                    if (nextDateStr) startDate = new Date(nextDateStr);
                                    else startDate.setDate(1);
                                }
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
                            updateAmount();
                            updateDueDate();
                        }

                        function updatePeriodEnd() {
                            if (durationSelect.value === 'Custom') return;
                            
                            const startDate = new Date(startInput.value);
                            if (isNaN(startDate.getTime())) return;

                            const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                            const endDate = new Date(startDate);
                            endDate.setMonth(endDate.getMonth() + months);
                            endDate.setDate(endDate.getDate() - 1);
                            
                            const year = endDate.getFullYear();
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const day = String(endDate.getDate()).padStart(2, '0');
                            endInput.value = `${year}-${month}-${day}`;
                        }

                        function updateAmount() {
                            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
                            if (!selectedOption || !selectedOption.value) return;

                            const basePrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                            const months = parseInt(durationSelect.options[durationSelect.selectedIndex].getAttribute('data-months')) || 1;
                            amountInput.value = basePrice * months;
                        }

                        function updateDueDate() {
                            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
                            if (!selectedOption || !selectedOption.value) return;

                            const dueDateStr = selectedOption.getAttribute('data-due');
                            const dueDateInput = document.getElementById('due_date');
                            
                            if (dueDateStr) {
                                dueDateInput.value = dueDateStr;
                            } else {
                                const defaultDue = new Date();
                                defaultDue.setDate(defaultDue.getDate() + 7);
                                const year = defaultDue.getFullYear();
                                const month = String(defaultDue.getMonth() + 1).padStart(2, '0');
                                const day = String(defaultDue.getDate()).padStart(2, '0');
                                dueDateInput.value = `${year}-${month}-${day}`;
                            }
                        }

                        customerSelect.addEventListener('change', updatePeriodStart);
                        durationSelect.addEventListener('change', () => {
                            updatePeriodEnd();
                            updateAmount();
                        });
                        startInput.addEventListener('change', updatePeriodEnd);

                        // Initial trigger
                        if (customerSelect.value) {
                            if (!startInput.value) {
                                updatePeriodStart();
                            } else {
                                updatePeriodEnd();
                                updateAmount();
                                updateDueDate();
                            }
                        }
                    });
                </script>
                <div>
                    <x-input-label for="amount" :value="__('Amount (Rp)')" />
                    <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="due_date" :value="__('Due Date')" />
                    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" value="{{ now()->addDays(7)->format('Y-m-d') }}" required />
                </div>
                <div>
                    <x-input-label for="notes" :value="__('Additional Info (Keterangan)')" />
                    <textarea id="notes" name="notes" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-700 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm" rows="3" placeholder="Contoh: Biaya Instalasi, Denda, dll"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('invoices.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Generate Invoice</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
