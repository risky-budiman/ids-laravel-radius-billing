<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Edit NAS: ') }} <span class="text-indigo-600 dark:text-indigo-400">{{ $router->nasname }}</span>
        </h2>
    </x-slot>

    <div class="glass max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <form action="{{ route('nas.update', $router->id) }}" method="POST" class="p-8">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <x-input-label for="nasname" :value="__('NAS IP Address (nasname)')" />
                    <x-text-input id="nasname" name="nasname" type="text" class="mt-1 block w-full" :value="old('nasname', $router->nasname)" required />
                </div>
                <div>
                    <x-input-label for="shortname" :value="__('Shortname')" />
                    <x-text-input id="shortname" name="shortname" type="text" class="mt-1 block w-full" :value="old('shortname', $router->shortname)" />
                </div>
                <div>
                    <x-input-label for="secret" :value="__('RADIUS Secret')" />
                    <x-text-input id="secret" name="secret" type="text" class="mt-1 block w-full" :value="old('secret', $router->secret)" required />
                </div>
                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $router->description)" required />
                </div>
            </div>
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('nas.index') }}" class="mr-4 text-gray-600 hover:underline">Cancel</a>
                <x-primary-button>Update NAS</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
