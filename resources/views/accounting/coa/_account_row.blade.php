@props(['account', 'groupedAccounts', 'depth' => 0, 'types'])

<tr class="{{ $depth == 0 ? 'bg-indigo-50/10 dark:bg-indigo-900/5' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }} transition-colors">
    <td class="px-6 py-4 font-mono text-sm {{ $depth == 0 ? 'font-black text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}" style="padding-left: {{ 1.5 + ($depth * 1.5) }}rem">
        {{ $account->code }}
    </td>
    <td class="px-6 py-4 {{ $depth == 0 ? 'font-bold text-gray-900 dark:text-white uppercase' : 'font-semibold text-gray-700 dark:text-gray-300' }}">
        {{ $account->name }}
    </td>
    <td class="px-6 py-4">
        @if($depth == 0)
            <span class="px-2 py-1 text-[10px] font-bold rounded-lg bg-{{ $types[$account->type]['color'] ?? 'gray' }}-50 text-{{ $types[$account->type]['color'] ?? 'gray' }}-600 uppercase">
                {{ $account->type }}
            </span>
        @else
            <span class="text-xs text-gray-400 italic">{{ $account->type }}</span>
        @endif
    </td>
    <td class="px-6 py-4 text-right font-mono text-sm {{ $depth == 0 ? 'font-bold text-gray-900 dark:text-white' : 'text-gray-900 dark:text-white' }}">
        Rp {{ number_format($account->balance, 2, ',', '.') }}
    </td>
    <td class="px-6 py-4 text-center">
        @if(auth()->user()->isAdministrator())
        <div class="flex justify-center space-x-2">
            <button @click="editAccount = { id: '{{ $account->id }}', code: '{{ $account->code }}', name: '{{ $account->name }}', type: '{{ $account->type }}', parent_id: '{{ $account->parent_id }}' }; $dispatch('open-modal', 'edit-account')" class="{{ $depth == 0 ? 'text-indigo-400 hover:text-indigo-600' : 'text-gray-400 hover:text-indigo-500' }} transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            </button>
            <button @click="deleteAccount = { id: '{{ $account->id }}', name: '{{ $account->name }}', code: '{{ $account->code }}' }; $dispatch('open-modal', 'confirm-account-deletion')" class="{{ $depth == 0 ? 'text-rose-400 hover:text-rose-600' : 'text-gray-400 hover:text-rose-500' }} transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
        @else
            <span class="text-xs text-gray-400 italic">View Only</span>
        @endif
    </td>
</tr>

@if($groupedAccounts->has($account->id))
    @foreach($groupedAccounts->get($account->id) as $child)
        @include('accounting.coa._account_row', [
            'account' => $child,
            'groupedAccounts' => $groupedAccounts,
            'depth' => $depth + 1,
            'types' => $types
        ])
    @endforeach
@endif
