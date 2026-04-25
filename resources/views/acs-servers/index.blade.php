<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('GenieACS Servers') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @if(session('success'))
            <div class="px-4 py-3 bg-green-100 border border-green-200 text-green-700 rounded-xl font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="px-4 py-3 bg-red-100 border border-red-200 text-red-700 rounded-xl">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Tambah Server -->
            <div class="lg:col-span-1">
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-indigo-50/50 dark:bg-indigo-900/20 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100">Add New Server</h3>
                    </div>
                    <form action="{{ route('acs-servers.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="name" value="Server Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. GenieACS Pusat" required />
                        </div>

                        <div>
                            <x-input-label for="url" value="API URL" />
                            <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" placeholder="http://10.0.0.1:7557" required />
                            <p class="mt-1 text-xs text-gray-500">Include port if necessary (default GenieACS is 7557)</p>
                        </div>

                        <div>
                            <x-input-label for="description" value="Description (Optional)" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" rows="3"></textarea>
                        </div>

                        <div class="flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" value="1" checked class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600">
                            <label for="is_active" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors shadow-sm shadow-indigo-500/30">
                                Save Server
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar Server -->
            <div class="lg:col-span-2">
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Server Name</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">URL</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($servers as $server)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $server->name }}
                                            @if($server->description)
                                                <p class="text-xs text-gray-500 font-normal">{{ $server->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-mono text-sm text-indigo-600 dark:text-indigo-400">
                                            {{ $server->url }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $server->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                                {{ $server->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-3">
                                                <form action="{{ route('acs-servers.destroy', $server->id) }}" method="POST" onsubmit="return confirm('Delete this server?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            No ACS Servers found. Add one to get started.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
