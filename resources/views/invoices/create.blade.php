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
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->username }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="amount" :value="__('Amount (Rp)')" />
                    <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="due_date" :value="__('Due Date')" />
                    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" required />
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('invoices.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Generate</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
