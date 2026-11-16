<footer class="mt-auto border-t border-surface-200 dark:border-surface-800">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 px-4 sm:px-6 lg:px-8 py-4 text-xs text-surface-500 dark:text-surface-400">
        <p>&copy; {{ date('Y') }} {{ get_setting('company_name', 'Radius ISP') }} · Developed by Rizki Budiman</p>
        <div class="flex items-center gap-4">
            @canroute('docs.index')
                <a href="{{ route('docs.index') }}" target="_blank" rel="noopener" class="hover:text-surface-800 dark:hover:text-surface-200 transition-colors">Dokumentasi</a>
            @endcanroute
            <span class="badge-gray font-mono">v{{ \App\Models\Changelog::latestVersion() }}</span>
        </div>
    </div>
</footer>
