<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-indigo-600/10 dark:bg-indigo-400/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                        {{ __('System & Server Logs') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">MikroTik-style system package manager, auto-updater, and live syslog</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                    Log Size: {{ $formattedSize }}
                </span>
                <form action="{{ route('server-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear the server log? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-sm shadow-rose-500/30">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Clear Syslog
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="systemUpdater()">
        <!-- Alerts -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-800 dark:text-emerald-200 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-2xl text-rose-800 dark:text-rose-200 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- MikroTik-Style System Package & Auto-Upgrade Card -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <!-- Header bar -->
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-black uppercase tracking-wider bg-indigo-500/30 border border-indigo-400/40 text-indigo-200 font-mono">
                        System Package
                    </span>
                    <span class="font-bold text-sm tracking-wide">RouterOS-Style Auto Upgrade</span>
                </div>
                <div class="text-xs text-gray-400 font-mono flex items-center space-x-2">
                    <span>Channel: <strong class="text-white">main (stable)</strong></span>
                    <span class="text-gray-600">•</span>
                    <span>Last Checked: <strong class="text-indigo-300" x-text="lastChecked || 'Not checked yet'"></strong></span>
                </div>
            </div>

            <div class="p-6">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Installed Version -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Installed Version</span>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-xl font-black text-gray-900 dark:text-white font-mono" x-text="'v' + currentVersion">v{{ app_version() }}</span>
                            <span class="text-xs text-gray-500 font-mono" x-text="'(' + currentCommit + ')'"></span>
                        </div>
                    </div>

                    <!-- Latest Available Version -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Latest on Remote</span>
                        <div class="flex items-baseline space-x-2">
                            <template x-if="isChecking">
                                <span class="text-sm font-bold text-indigo-600 animate-pulse">Checking remote...</span>
                            </template>
                            <template x-if="!isChecking">
                                <span class="text-xl font-black font-mono" :class="isUpdateAvailable ? 'text-amber-500' : 'text-emerald-500'" x-text="remoteCommit ? (isUpdateAvailable ? 'Update Ready' : 'Up to Date') : 'Unknown'">
                                    Up to Date
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Upgrade Status</span>
                        <div class="flex items-center space-x-2 mt-1">
                            <div class="w-2.5 h-2.5 rounded-full" :class="isUpdateAvailable ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'"></div>
                            <span class="text-xs font-bold font-mono" :class="isUpdateAvailable ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'" x-text="statusMessage">
                                System is up to date
                            </span>
                        </div>
                    </div>

                    <!-- Environment -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Runtime Environment</span>
                        <span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300 block truncate">
                            PHP {{ phpversion() }} • Laravel {{ app()->version() }}
                        </span>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-3 w-full sm:w-auto">
                        <!-- Check for Updates Button -->
                        <button @click="checkForUpdates()" :disabled="isChecking || isUpdating" class="w-full sm:w-auto px-5 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold uppercase tracking-wider rounded-xl transition-all flex items-center justify-center disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" :class="{'animate-spin': isChecking}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span x-text="isChecking ? 'Checking...' : 'Check For Updates'">Check For Updates</span>
                        </button>

                        <!-- Download & Install / Update Now Button -->
                        <button @click="startUpdate()" :disabled="isChecking || isUpdating" class="w-full sm:w-auto px-6 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-lg flex items-center justify-center disabled:opacity-50"
                                :class="isUpdateAvailable ? 'bg-amber-500 hover:bg-amber-600 text-slate-900 shadow-amber-500/30 animate-pulse' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/30'">
                            <svg class="w-4 h-4 mr-2" :class="{'animate-spin': isUpdating}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span x-text="isUpdating ? 'Updating System...' : (isUpdateAvailable ? 'Download & Install' : 'Force Upgrade')">Download & Install</span>
                        </button>
                    </div>

                    <!-- Toggle What's New -->
                    <template x-if="newCommits.length > 0">
                        <button @click="showCommits = !showCommits" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center">
                            <span x-text="showCommits ? 'Hide Changelog' : 'View ' + newCommits.length + ' Changes'"></span>
                            <svg class="w-4 h-4 ml-1 transform transition-transform" :class="{'rotate-180': showCommits}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </template>
                </div>

                <!-- Expandable Commits List -->
                <div x-show="showCommits" x-collapse class="mt-4 p-4 bg-gray-900 text-gray-300 rounded-2xl border border-gray-800 text-xs font-mono space-y-1.5 overflow-x-auto">
                    <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-2">Recent Commits / What's New:</span>
                    <template x-for="commit in newCommits" :key="commit">
                        <div class="flex items-center space-x-2 text-emerald-400">
                            <span>•</span>
                            <span x-text="commit"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Live Server Log Terminal Card -->
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <!-- Terminal Header -->
            <div class="bg-gray-900 px-6 py-3 border-b border-gray-800 flex justify-between items-center text-white">
                <div class="flex items-center space-x-3">
                    <div class="flex space-x-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    </div>
                    <span class="text-xs font-mono text-gray-400 font-bold">storage/logs/laravel.log</span>
                </div>

                <div class="flex items-center space-x-3 text-xs">
                    <button onclick="window.location.reload()" class="px-2.5 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-gray-300 transition-colors font-mono flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Logs
                    </button>
                </div>
            </div>

            <!-- Terminal Body -->
            <div class="p-6 bg-slate-950 text-gray-300 overflow-x-auto overflow-y-auto font-mono text-xs leading-relaxed" style="max-height: 65vh;" id="log-container">
                @if(empty(trim($logs)))
                    <div class="text-gray-600 italic py-8 text-center">Log file is currently empty.</div>
                @else
                    <pre class="font-mono text-emerald-400 whitespace-pre-wrap break-all select-text leading-5">{{ $logs }}</pre>
                @endif
            </div>
        </div>

        <!-- Update Execution Modal with Real-time Terminal -->
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
            <div class="relative bg-gray-900 text-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-800">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gray-800/80 border-b border-gray-700 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></div>
                        <h3 class="font-bold text-sm font-mono uppercase tracking-wider text-indigo-300">System Upgrade in Progress</h3>
                    </div>
                    <template x-if="!isUpdating">
                        <button @click="showModal = false; window.location.reload();" class="text-gray-400 hover:text-white font-bold">&times;</button>
                    </template>
                </div>

                <!-- Modal Body (Terminal Output) -->
                <div class="p-6 space-y-4">
                    <div class="bg-black/80 rounded-2xl p-4 border border-gray-800 font-mono text-xs text-green-400 overflow-y-auto max-h-80 leading-relaxed whitespace-pre-wrap" id="update-terminal" x-text="updateOutput || 'Initializing upgrade process...'"></div>

                    <div class="flex justify-between items-center pt-2">
                        <template x-if="isUpdating">
                            <div class="flex items-center text-xs text-indigo-400 font-mono animate-pulse">
                                <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Running upgrade steps, please wait...</span>
                            </div>
                        </template>

                        <template x-if="!isUpdating">
                            <button @click="showModal = false; window.location.reload();" class="ml-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition-all">
                                Done (Reload Dashboard)
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function systemUpdater() {
            return {
                currentVersion: '{{ app_version() }}',
                currentCommit: '',
                remoteCommit: '',
                isUpdateAvailable: false,
                isChecking: false,
                isUpdating: false,
                statusMessage: 'Ready to check for updates',
                lastChecked: '',
                newCommits: [],
                showCommits: false,
                showModal: false,
                updateOutput: '',

                init() {
                    // Auto scroll syslog container
                    const container = document.getElementById('log-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                    // Auto check on load
                    this.checkForUpdates();
                },

                checkForUpdates() {
                    this.isChecking = true;
                    this.statusMessage = 'Connecting to remote repository...';

                    fetch('{{ route('system.update.check') }}')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.currentVersion = data.current_version;
                                this.currentCommit = data.current_commit;
                                this.remoteCommit = data.remote_commit;
                                this.isUpdateAvailable = data.is_update_available;
                                this.statusMessage = data.status_message;
                                this.lastChecked = data.checked_at;
                                this.newCommits = data.new_commits || [];
                            } else {
                                this.statusMessage = 'Check failed: ' + (data.message || 'Unknown');
                            }
                        })
                        .catch(err => {
                            this.statusMessage = 'Network error while checking updates';
                        })
                        .finally(() => {
                            this.isChecking = false;
                        });
                },

                startUpdate() {
                    if (!confirm('Start system upgrade now? All changes from origin/main will be applied.')) {
                        return;
                    }

                    this.isUpdating = true;
                    this.showModal = true;
                    this.updateOutput = '🚀 [1/6] Connecting to server & starting update pipeline...\n';

                    fetch('{{ route('system.update.run') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.updateOutput = data.logs || (data.message || 'Update completed.');
                        if (data.success) {
                            this.currentVersion = data.new_version;
                            this.currentCommit = data.new_commit;
                            this.isUpdateAvailable = false;
                            this.statusMessage = 'System is up to date';
                        }
                    })
                    .catch(err => {
                        this.updateOutput += '\n❌ Upgrade Error: ' + err.message;
                    })
                    .finally(() => {
                        this.isUpdating = false;
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
