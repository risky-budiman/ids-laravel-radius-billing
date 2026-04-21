<x-app-layout>
    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen font-sans">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            
            <!-- HEADER (Sesuai Referensi) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-12 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white tracking-tight">Change Log</h2>
                </div>
                
                @if(auth()->user()->isAdministrator())
                    <button x-data="" @click="$dispatch('open-modal', 'add-changelog')" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold uppercase tracking-wider rounded-lg transition-colors shadow-sm">
                        + NEW UPDATE
                    </button>
                @endif
            </div>

            <!-- TIMELINE CONTAINER -->
            <div class="relative">
                <!-- Central Vertical Line -->
                <div class="hidden md:block absolute left-1/2 transform -translate-x-1/2 h-full w-px bg-gray-300 dark:bg-gray-700"></div>

                <div class="space-y-4">
                    @forelse($changelogs as $index => $log)
                        <!-- ROW -->
                        <div class="md:grid md:grid-cols-9 flex flex-col items-center">
                            
                            @if($index % 2 == 0)
                                <!-- EVEN: Date Left, Icon Center, Card Right -->
                                <div class="md:col-span-4 flex items-center justify-end w-full px-6 mb-2 md:mb-0">
                                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wide text-right">
                                        {{ \Carbon\Carbon::parse($log->release_date)->translatedFormat('d F Y') }}
                                    </p>
                                </div>

                                <div class="md:col-span-1 flex justify-center items-center relative py-4">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 border-4 border-white dark:border-gray-900 shadow-sm flex items-center justify-center text-white z-10">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <div class="md:col-span-4 w-full px-6 py-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-dashed border-blue-400 dark:border-blue-500 p-6 rounded-xl shadow-sm group">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ $log->version }}</h3>
                                            <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400">Update</span>
                                        </div>

                                        <div class="space-y-2">
                                            @if($log->title)
                                                <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm mb-2">{{ $log->title }}</h4>
                                            @endif
                                            <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                @php
                                                    $lines = explode("\n", $log->description);
                                                @endphp
                                                @foreach($lines as $line)
                                                    @if(trim($line))
                                                        <p class="flex items-start">
                                                            <span class="text-blue-500 mr-2 font-bold">+</span>
                                                            {{ ltrim(trim($line), '- +') }}
                                                        </p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                        @if(auth()->user()->isAdministrator())
                                            <div class="mt-4 pt-3 border-t border-blue-200/50 dark:border-blue-800/50 flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                                <form action="{{ route('changelog.destroy', $log) }}" method="POST" onsubmit="return confirm('Erase this history?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            @else
                                <!-- ODD: Card Left, Icon Center, Date Right -->
                                <div class="md:col-span-4 w-full px-6 py-4 order-2 md:order-1">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-dashed border-blue-400 dark:border-blue-500 p-6 rounded-xl shadow-sm group">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ $log->version }}</h3>
                                            <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400">Update</span>
                                        </div>

                                        <div class="space-y-2">
                                            @if($log->title)
                                                <h4 class="font-bold text-gray-800 dark:text-gray-200 text-sm mb-2">{{ $log->title }}</h4>
                                            @endif
                                            <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                @php
                                                    $lines = explode("\n", $log->description);
                                                @endphp
                                                @foreach($lines as $line)
                                                    @if(trim($line))
                                                        <p class="flex items-start">
                                                            <span class="text-blue-500 mr-2 font-bold">+</span>
                                                            {{ ltrim(trim($line), '- +') }}
                                                        </p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>

                                        @if(auth()->user()->isAdministrator())
                                            <div class="mt-4 pt-3 border-t border-blue-200/50 dark:border-blue-800/50 flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                                <form action="{{ route('changelog.destroy', $log) }}" method="POST" onsubmit="return confirm('Erase this history?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="md:col-span-1 flex justify-center items-center relative py-4 order-1 md:order-2">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 border-4 border-white dark:border-gray-900 shadow-sm flex items-center justify-center text-white z-10">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <div class="md:col-span-4 flex items-center justify-start w-full px-6 mb-2 md:mb-0 order-3 md:order-3">
                                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wide">
                                        {{ \Carbon\Carbon::parse($log->release_date)->translatedFormat('d F Y') }}
                                    </p>
                                </div>
                            @endif

                        </div>
                    @empty
                        <div class="text-center py-20 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                            <p class="text-gray-400 font-medium italic">No updates recorded yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL -->
    <x-modal name="add-changelog" focusable>
        <form method="post" action="{{ route('changelog.store') }}" class="p-8">
            @csrf
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">New Application Release</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <x-input-label for="version" value="Version (e.g. v1.0.1)" class="font-bold text-xs uppercase text-gray-500 mb-2" />
                    <x-text-input id="version" name="version" type="text" class="block w-full border-gray-300 rounded-lg" placeholder="v1.0.1" required />
                </div>
                <div>
                    <x-input-label for="release_date" value="Release Date" class="font-bold text-xs uppercase text-gray-500 mb-2" />
                    <x-text-input id="release_date" name="release_date" type="date" class="block w-full border-gray-300 rounded-lg" value="{{ date('Y-m-d') }}" required />
                </div>
            </div>

            <div class="mb-6">
                <x-input-label for="title" value="Update Title" class="font-bold text-xs uppercase text-gray-500 mb-2" />
                <x-text-input id="title" name="title" type="text" class="block w-full border-gray-300 rounded-lg" placeholder="Brief summary of this release" required />
            </div>

            <div class="mb-6">
                <x-input-label for="type" value="Category" class="font-bold text-xs uppercase text-gray-500 mb-2" />
                <select id="type" name="type" class="block w-full border-gray-300 rounded-lg focus:ring-indigo-500">
                    <option value="feature">✨ New Feature</option>
                    <option value="fix">🐛 Bug Fix</option>
                    <option value="security">🛡️ Security Patch</option>
                </select>
            </div>

            <div class="mb-8">
                <x-input-label for="description" value="Details (One per line)" class="font-bold text-xs uppercase text-gray-500 mb-2" />
                <textarea id="description" name="description" rows="5" class="block w-full border-gray-300 rounded-lg focus:ring-indigo-500" placeholder="List the changes here..." required></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="rounded-lg">{{ __('Cancel') }}</x-secondary-button>
                <x-primary-button class="bg-indigo-600 rounded-lg">{{ __('Publish Log') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>

