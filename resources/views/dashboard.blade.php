<x-app-layout>
    <style>
        .stat-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            perspective: 1000px;
        }
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
        }
        .stat-card-indigo:hover { box-shadow: 0 25px 30px -12px rgba(79, 70, 229, 0.25); border-color: rgba(79, 70, 229, 0.4); }
        .stat-card-green:hover { box-shadow: 0 25px 30px -12px rgba(16, 185, 129, 0.25); border-color: rgba(16, 185, 129, 0.4); }
        .stat-card-purple:hover { box-shadow: 0 25px 30px -12px rgba(139, 92, 246, 0.25); border-color: rgba(139, 92, 246, 0.4); }
        .stat-card-orange:hover { box-shadow: 0 25px 30px -12px rgba(249, 115, 22, 0.25); border-color: rgba(249, 115, 22, 0.4); }
        
        .glass-premium {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(229, 231, 235, 0.8);
        }
        .dark .glass-premium {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>

    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100/80 border border-green-200 text-green-700 rounded-2xl dark:bg-green-900/30 dark:border-green-800 dark:text-green-400 font-medium animate-pulse">
            {{ session('success') }}
        </div>
    @endif

    <!-- Profile Summary Section -->
    <div class="mb-8 p-6 glass-premium bg-white/80 dark:bg-gray-800/80 rounded-[2rem] shadow-xl shadow-indigo-500/5 border border-white/50 dark:border-gray-700/50 flex flex-col md:flex-row items-center justify-between">
        <div class="flex items-center space-x-6">
            <div class="relative">
                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-24 w-24 rounded-3xl object-cover border-4 border-white dark:border-gray-700 shadow-2xl shadow-indigo-500/20">
                <div class="absolute -bottom-2 -right-2 h-8 w-8 bg-green-500 border-4 border-white dark:border-gray-800 rounded-full shadow-lg"></div>
            </div>
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Selamat Datang, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
                <div class="flex items-center mt-1 space-x-3">
                    <p class="text-gray-500 dark:text-gray-400 font-medium flex items-center">
                        <span class="px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest mr-3">
                            {{ auth()->user()->role }}
                        </span>
                        {{ auth()->user()->email }}
                    </p>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <p id="live-clock" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ now()->translatedFormat('l, d F Y | H:i:s') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-6 md:mt-0">
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl font-bold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Edit Profile
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat Card 1 -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Subscribers</span>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($totalSubscribers) }}</p>
                </div>
            </div>
            <div class="h-1 w-full bg-gray-50 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full w-2/3"></div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 rounded-2xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 group-hover:bg-green-600 group-hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">Active Users</span>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($activeUsers) }}</p>
                </div>
            </div>
            <div class="h-1 w-full bg-gray-50 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full w-full"></div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-purple-500 uppercase tracking-widest">Revenue</span>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1"><span class="text-sm font-bold opacity-50">Rp</span> {{ number_format($revenue, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="h-1 w-full bg-gray-50 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full w-3/4"></div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 rounded-2xl bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 group-hover:bg-orange-600 group-hover:text-white transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-orange-500 uppercase tracking-widest">Unpaid</span>
                    <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($unpaidInvoices) }}</p>
                </div>
            </div>
            <div class="h-1 w-full bg-gray-50 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-orange-500 rounded-full w-1/4"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="space-y-8">
        <!-- Row 2: Quick Actions & Live Traffic -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Quick Actions -->
            <div class="glass-premium bg-white/80 dark:bg-gray-800/80 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full mr-3"></span>
                    Quick Actions
                </h3>
                <div class="space-y-4">
                    <a href="{{ route('customers.create') }}" class="w-full flex items-center p-5 rounded-2xl border border-gray-100 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-700 hover:shadow-xl hover:shadow-indigo-500/10 hover:border-indigo-200 dark:hover:border-indigo-800 transition-all group">
                        <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mr-4 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <div class="text-left">
                            <p class="font-bold text-gray-900 dark:text-gray-100">Add Subscriber</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Register a new user</p>
                        </div>
                    </a>
                    @if(!auth()->user()->isKasir())
                    <form action="{{ route('invoices.generate-automated') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center p-5 rounded-2xl border border-gray-100 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-700 hover:shadow-xl hover:shadow-green-500/10 hover:border-green-200 dark:hover:border-green-800 transition-all group">
                            <div class="p-3 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 mr-4 group-hover:bg-green-600 group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-gray-900 dark:text-gray-100">Generate Invoices</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Automated billing cycle</p>
                            </div>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Live Traffic & Network Status -->
            <div class="lg:col-span-2 glass-premium bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                        <span class="w-2 h-8 bg-green-600 rounded-full mr-3"></span>
                        Live Traffic & Network Status
                    </h3>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2 mr-4">
                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400 uppercase tracking-widest">Live</span>
                        </div>
                        
                        @if(auth()->user()->isAdmin())
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('radius.clear-stale') }}" method="POST" onsubmit="return confirm('Bersihkan sesi menggantung (idle > 2 jam)?')">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-amber-600 hover:text-white transition-all border border-amber-100 dark:border-amber-800/30">
                                    Clear Stale
                                </button>
                            </form>
                            <form action="{{ route('radius.disconnect-all') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memutuskan SELURUH sesi online saat ini?')">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-rose-600 hover:text-white transition-all border border-rose-100 dark:border-rose-800/30">
                                    Disconnect All
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Mini Stats Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 pb-2">
                    <!-- Online Now -->
                    <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl p-4 border border-emerald-100 dark:border-emerald-800/30">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Online</span>
                        </div>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ number_format($onlineNow) }}</p>
                    </div>
                    <!-- Upload -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-4 border border-blue-100 dark:border-blue-800/30">
                        <div class="flex items-center space-x-2 mb-2">
                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Upload</span>
                        </div>
                        <p class="text-xl font-black text-blue-700 dark:text-blue-300">
                            @if($totalUpload > 1073741824)
                                {{ number_format($totalUpload / 1073741824, 2) }} <span class="text-xs font-bold opacity-60">GB</span>
                            @else
                                {{ number_format($totalUpload / 1048576, 1) }} <span class="text-xs font-bold opacity-60">MB</span>
                            @endif
                        </p>
                    </div>
                    <!-- Download -->
                    <div class="bg-gradient-to-br from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-2xl p-4 border border-green-100 dark:border-green-800/30">
                        <div class="flex items-center space-x-2 mb-2">
                            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400 uppercase tracking-widest">Download</span>
                        </div>
                        <p class="text-xl font-black text-green-700 dark:text-green-300">
                            @if($totalDownload > 1073741824)
                                {{ number_format($totalDownload / 1073741824, 2) }} <span class="text-xs font-bold opacity-60">GB</span>
                            @else
                                {{ number_format($totalDownload / 1048576, 1) }} <span class="text-xs font-bold opacity-60">MB</span>
                            @endif
                        </p>
                    </div>
                    <!-- Auth Today -->
                    <div class="bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-2xl p-4 border border-purple-100 dark:border-purple-800/30">
                        <div class="flex items-center space-x-2 mb-2">
                            <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest">Auth</span>
                        </div>
                        <p class="text-lg font-black">
                            <span class="text-emerald-600 dark:text-emerald-400">{{ $authAcceptToday }}</span>
                            <span class="text-gray-300 dark:text-gray-600 mx-1">/</span>
                            <span class="text-rose-600 dark:text-rose-400">{{ $authRejectToday }}</span>
                        </p>
                    </div>
                </div>

                <!-- Traffic Chart -->
                <div class="px-6 py-4">
                    <canvas id="trafficChart" height="140"></canvas>
                </div>

                <!-- Top Bandwidth Users -->
                @if($topUsers->count() > 0)
                <div class="px-6 pb-6">
                    <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 px-2">Top Bandwidth Consumers</h4>
                    <div class="space-y-2">
                        @foreach($topUsers as $idx => $user)
                        <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-gray-50/80 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black
                                    {{ $idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ $idx + 1 }}
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100 font-mono">{{ $user->username }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $user->framedipaddress ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    @if($user->total_traffic > 1073741824)
                                        {{ number_format($user->total_traffic / 1073741824, 2) }} GB
                                    @else
                                        {{ number_format($user->total_traffic / 1048576, 1) }} MB
                                    @endif
                                </p>
                                <p class="text-[10px] text-gray-400">{{ gmdate('H:i:s', $user->acctsessiontime ?? 0) }} uptime</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="px-6 pb-6 text-center text-gray-400 dark:text-gray-500 py-4">
                    <p class="text-sm font-medium">No active sessions at the moment</p>
                </div>
                @endif
            </div>
        </div>

        @if(auth()->user()->isAdmin())
        <!-- Row 3: Recent Activity Log (Separate and clean) -->
        <div class="glass-premium bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full mr-3"></span>
                    Recent Activity Audit
                </h3>
                <a href="{{ route('activity-logs.index') }}" class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest hover:underline flex items-center">
                    View Full Logs
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @forelse($latestActivities as $activity)
                    <div class="px-8 py-5 flex items-center justify-between hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-all">
                        <div class="flex items-center space-x-4">
                            <div class="p-2 rounded-xl 
                                {{ $activity->action === 'created' ? 'bg-green-50 text-green-600' : 
                                  ($activity->action === 'updated' ? 'bg-blue-50 text-blue-600' : 
                                  ($activity->action === 'login' || $activity->action === 'logout' ? 'bg-indigo-50 text-indigo-600' : 'bg-red-50 text-red-600')) }} dark:bg-gray-700">
                                @if($activity->action === 'created')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                @elseif($activity->action === 'updated')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                @elseif($activity->action === 'login')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                @elseif($activity->action === 'logout')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $activity->description }}</p>
                                
                                @if($activity->action === 'updated' && isset($activity->properties['new']))
                                    <p class="text-[9px] font-medium text-amber-600 dark:text-amber-400 uppercase tracking-tighter mb-1">
                                        Changes: {{ implode(', ', array_keys($activity->properties['new'])) }}
                                    </p>
                                @endif

                                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">By <span class="text-indigo-600">{{ $activity->user->name ?? 'System' }}</span> &bull; {{ $activity->ip_address }}</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1 rounded-full">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-500">No recent activity.</div>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</x-app-layout>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trafficChart');
    if (!ctx) return;

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textColor = isDark ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.4)';

    const uploadGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    uploadGradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
    uploadGradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

    const downloadGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    downloadGradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
    downloadGradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Upload (MB)',
                    data: @json($chartUpload),
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: uploadGradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgb(99, 102, 241)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                },
                {
                    label: 'Download (MB)',
                    data: @json($chartDownload),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: downloadGradient,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgb(16, 185, 129)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11, weight: '600' },
                        color: textColor,
                        padding: 16,
                    }
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(17,24,39,0.95)' : 'rgba(255,255,255,0.95)',
                    titleColor: isDark ? '#e5e7eb' : '#111827',
                    bodyColor: isDark ? '#9ca3af' : '#6b7280',
                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 12,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    usePointStyle: true,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: textColor,
                        font: { size: 10, weight: '500' },
                        maxRotation: 0,
                        maxTicksLimit: 12,
                    },
                    border: { display: false }
                },
                y: {
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: textColor,
                        font: { size: 10, weight: '500' },
                        callback: function(val) { return val + ' MB'; }
                    },
                    border: { display: false },
                    beginAtZero: true,
                }
            }
        }
    });
    
    // Live Clock Update
    function updateClock() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        const formatter = new Intl.DateTimeFormat('id-ID', options);
        const parts = formatter.formatToParts(now);
        
        let dateStr = "";
        let timeStr = "";
        
        parts.forEach(part => {
            if (part.type === 'hour' || part.type === 'minute' || part.type === 'second' || part.type === 'literal') {
                if (part.value === ' ' && timeStr === "") return;
                timeStr += part.value;
            } else {
                dateStr += part.value;
            }
        });

        const clockElement = document.getElementById('live-clock');
        if (clockElement) {
            // Clean up strings
            dateStr = dateStr.trim().replace(/,$/, '');
            timeStr = timeStr.trim().replace(/^\./, '').replace(/^,/, '').trim();
            
            clockElement.innerHTML = `
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                ${dateStr} | ${timeStr}
            `;
        }
    }
    
    setInterval(updateClock, 1000);
});
</script>
