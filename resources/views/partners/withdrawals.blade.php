<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Commission Withdrawals') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                            @if(auth()->user()->isAdmin())
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Partner</th>
                            @endif
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Bank Info</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            @if(auth()->user()->isAdmin())
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($withdrawals as $wd)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $wd->request_date }}</td>
                            @if(auth()->user()->isAdmin())
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $wd->partner->name }}</div>
                                </td>
                            @endif
                            <td class="px-6 py-4 font-black text-gray-900 dark:text-white">Rp {{ number_format($wd->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-700 dark:text-gray-300">{{ $wd->bank_name }} - {{ $wd->bank_account_number }}</div>
                                <div class="text-[10px] text-gray-500">{{ $wd->bank_account_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $colors = [
                                        'pending' => 'bg-amber-50 text-amber-600',
                                        'approved' => 'bg-blue-50 text-blue-600',
                                        'paid' => 'bg-emerald-50 text-emerald-600',
                                        'rejected' => 'bg-rose-50 text-rose-600',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $colors[$wd->status] ?? 'bg-gray-100' }}">
                                    {{ $wd->status }}
                                </span>
                            </td>
                            @if(auth()->user()->isAdmin())
                            <td class="px-6 py-4 text-right">
                                @if($wd->status === 'pending')
                                <button x-data="" @click="$dispatch('open-modal', 'process-wd-{{ $wd->id }}')" class="text-xs font-bold text-indigo-600 hover:underline">
                                    Process
                                </button>

                                <x-modal name="process-wd-{{ $wd->id }}" focusable>
                                    <form method="post" action="{{ route('partners.withdrawals.process', $wd) }}" class="p-8 text-left">
                                        @csrf
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">
                                            Process Withdrawal Request
                                        </h2>

                                        <div class="space-y-4">
                                            <div>
                                                <x-input-label for="status" value="Action" />
                                                <select name="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                    <option value="paid">Pay Now (Confirm & Journal)</option>
                                                    <option value="approved">Approve (Pending Payment)</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="bank_account_id" value="Source Fund Account (If Paid)" />
                                                <select name="bank_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                    @foreach(\App\Models\BankAccount::where('is_active', true)->get() as $acc)
                                                        <option value="{{ $acc->id }}">{{ $acc->bank_name }} - {{ $acc->account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="notes" value="Notes" />
                                                <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                            </div>
                                        </div>

                                        <div class="mt-8 flex justify-end">
                                            <x-secondary-button x-on:click="$dispatch('close')">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>

                                            <x-primary-button class="ms-3">
                                                {{ __('Save Status') }}
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </x-modal>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
