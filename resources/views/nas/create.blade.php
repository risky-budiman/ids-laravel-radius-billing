<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add Router / NAS') }}
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('nas.store') }}" method="POST" class="p-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <x-input-label for="nasname" :value="__('NAS IP Address (nasname)')" />
                    <x-text-input id="nasname" name="nasname" type="text" class="mt-1 block w-full" required />
                    <p class="mt-1 text-xs text-gray-500">The IP address of the Mikrotik/Router.</p>
                </div>
                <div>
                    <x-input-label for="shortname" :value="__('Shortname')" />
                    <x-text-input id="shortname" name="shortname" type="text" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="secret" :value="__('RADIUS Secret')" />
                    <x-text-input id="secret" name="secret" type="text" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" value="Mikrotik Router" required />
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('nas.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Save NAS</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
