<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    WhatsApp Templates
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola format dan teks pesan notifikasi otomatis WhatsApp.
                </p>
            </div>
            <div>
                <form action="{{ route('whatsapp-templates.reset') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan semua template ke bawaan sistem? Perubahan yang Anda buat akan hilang.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset ke Default
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl dark:bg-emerald-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($templates as $template)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm overflow-hidden flex flex-col transition-all hover:shadow-md hover:border-emerald-200 dark:hover:border-emerald-900/50">
                        <div class="p-6 flex-grow">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-1" title="{{ $template->name }}">{{ $template->name }}</h3>
                                @if($template->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Nonaktif
                                    </span>
                                @endif
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 mb-4 h-32 overflow-hidden relative">
                                <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-mono text-xs line-clamp-5">{{ $template->message }}</p>
                                <div class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-gray-50 dark:from-gray-900/50 to-transparent"></div>
                            </div>
                            
                            <div class="flex flex-wrap gap-1 mt-auto">
                                @php
                                    $vars = json_decode($template->variables_description, true) ?? [];
                                @endphp
                                @foreach(array_slice($vars, 0, 3) as $var)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-mono">
                                        {{ $var }}
                                    </span>
                                @endforeach
                                @if(count($vars) > 3)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        +{{ count($vars) - 3 }} lainnya
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-800/80 px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ route('whatsapp-templates.edit', $template) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm shadow-emerald-500/20">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit Template
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum ada template</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Silakan jalankan tombol Reset ke Default di atas.</p>
                    </div>
                @endforelse
            </div>
            
        </div>
    </div>
</x-app-layout>
