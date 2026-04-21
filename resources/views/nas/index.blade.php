<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Routers (NAS)') }}
            </h2>
            <a href="{{ route('nas.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                + Add NAS
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shortname</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Secret</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($routers as $router)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-indigo-600 dark:text-indigo-400">{{ $router->nasname }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $router->shortname }}</td>
                            <td class="px-6 py-4 text-gray-500 font-mono tracking-widest">••••••••</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $router->description }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('nas.destroy', $router->id) }}" method="POST" onsubmit="return confirm('Delete this NAS?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No Routers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 pb-4">
            {{ $routers->links() }}
        </div>
    </div>
</x-app-layout>
