<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Server Logs') }}
            </h2>
            <div class="flex space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    File Size: {{ $formattedSize }}
                </span>
                <form action="{{ route('server-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear the server log? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Clear Logs
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-gray-900 overflow-hidden shadow-xl sm:rounded-lg border border-gray-700">
                <!-- Terminal Header -->
                <div class="bg-gray-800 px-4 py-2 border-b border-gray-700 flex items-center">
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    </div>
                    <div class="mx-auto text-gray-400 text-xs font-mono">laravel.log</div>
                </div>
                
                <!-- Terminal Body -->
                <div class="p-4 overflow-x-auto overflow-y-auto" style="max-height: 70vh;" id="log-container">
                    @if(empty(trim($logs)))
                        <div class="text-gray-500 italic font-mono text-sm">Log file is currently empty.</div>
                    @else
                        <pre class="font-mono text-sm text-green-400 whitespace-pre-wrap break-all leading-relaxed" style="font-family: 'Fira Code', 'Courier New', Courier, monospace;">{{ $logs }}</pre>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Auto scroll to bottom -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var container = document.getElementById('log-container');
            container.scrollTop = container.scrollHeight;
        });
    </script>
</x-app-layout>
