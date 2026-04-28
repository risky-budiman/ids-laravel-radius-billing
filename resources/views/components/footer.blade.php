<footer class="mt-auto">
    <div class="px-6 py-4 border-t border-gray-200/60 dark:border-gray-800/60 text-center sm:text-left sm:flex sm:items-center sm:justify-between text-sm text-gray-500 dark:text-gray-500 transition-colors">
        <div>
            &copy; {{ date('Y') }} {{ get_setting('company_name', 'By Rizki Budiman') }}. All rights reserved.
        </div>
        <div class="mt-2 sm:mt-0 flex items-center justify-center space-x-4">
            <a href="#" class="hover:text-gray-800 dark:hover:text-gray-300 transition-colors">Support</a>
            <a href="{{ route('docs.index') }}" target="_blank" class="hover:text-gray-800 dark:hover:text-gray-300 transition-colors">Documentation</a>
            <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-gray-400 font-mono">{{ \App\Models\Changelog::latestVersion() }}</span>
        </div>
    </div>
</footer>
