<x-app-layout>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
        
        .dashboard-container {
            font-family: 'Outfit', sans-serif;
        }

        .glass-card {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        }
        
        .dark .glass-card {
            background-color: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        .stat-card-glow {
            position: relative;
            overflow: hidden;
        }
        .stat-card-glow::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: scale(0);
            transition: transform 0.6s ease-out;
        }
        .stat-card-glow:hover::after {
            transform: scale(1);
        }

        .chart-container {
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.05));
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .float-animation { animation: float 6s ease-in-out infinite; }
    </style>

    <div class="dashboard-container pb-12">
        <!-- Dynamic Header & Greeting -->
        <div class="mb-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="animate-fade-in">
                @php
                    $hour = now()->hour;
                    $greeting = 'Selamat Malam';
                    if ($hour >= 5 && $hour < 11) $greeting = 'Selamat Pagi';
                    elseif ($hour >= 11 && $hour < 15) $greeting = 'Selamat Siang';
                    elseif ($hour >= 15 && $hour < 18) $greeting = 'Selamat Sore';
                @endphp
                <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                    {{ $greeting }}, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">{{ explode(' ', auth()->user()->name)[0] }}!</span>
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 font-medium flex items-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 mr-3">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        {{ auth()->user()->role }}
                    </span>
                    Monitoring network performance and billing today.
                </p>
            </div>
            
            <div class="flex items-center space-x-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-md p-2 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm animate-fade-in-right">
                <div class="px-4 py-2 text-right border-r border-gray-100 dark:border-gray-700">
                    <p id="live-date" class="text-[10px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-widest">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <p id="live-clock-detailed" class="text-sm font-black text-gray-800 dark:text-gray-100 font-mono">00:00:00</p>
                </div>
                <button onclick="window.location.reload()" class="p-3 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-xl transition-all active:scale-95" title="Refresh Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        </div>

        <!-- Stats Grid: Re-imagined -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
            <!-- Stat: Subscribers -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em]">Total Clients</p>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($totalSubscribers) }}</h2>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-green-500 font-bold text-xs flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path></svg>
                        Healthy Growth
                    </span>
                </div>
            </div>

            <!-- Stat: Active RADIUS Sessions -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.2em]">Live Sessions</p>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($onlineNow) }}</h2>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-gray-500 dark:text-gray-400 font-bold text-xs">Currently Authenticated</span>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
            <!-- Stat: Monthly Revenue -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-purple-500 uppercase tracking-[0.2em]">MTD Revenue</p>
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><span class="text-sm font-bold opacity-30">Rp</span> {{ number_format($revenue, 0, ',', '.') }}</h2>
                    </div>
                </div>
                <div class="text-gray-400 text-[10px] font-bold uppercase">{{ now()->format('F Y') }} Collection</div>
            </div>

            <!-- Stat: Critical Invoices -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-[0.2em]">Unpaid Tags</p>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($unpaidInvoices) }}</h2>
                    </div>
                </div>
                <div class="text-orange-600 dark:text-orange-400 text-xs font-bold flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    Requires Attention
                </div>
            </div>
            @else
            <!-- Stat: Auth Success Today -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em]">Auth Success</p>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($authAcceptToday) }}</h2>
                    </div>
                </div>
                <div class="text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-wider">Accepted Today</div>
            </div>

            <!-- Stat: Auth Failures Today -->
            <div class="glass-card stat-card-glow p-7 rounded-[2rem] transition-all hover:-translate-y-2 group">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white shadow-lg shadow-rose-500/20 group-hover:rotate-6 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-rose-500 uppercase tracking-[0.2em]">Auth Failed</p>
                        <h2 class="text-4xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($authRejectToday) }}</h2>
                    </div>
                </div>
                <div class="text-rose-600 dark:text-rose-400 text-xs font-bold uppercase tracking-wider">Rejected Today</div>
            </div>
            @endif
        </div>

        @if(auth()->user()->isAdmin())
        <!-- Monthly Financial Performance -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Income This Month -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-emerald-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Pendapatan Bulan Ini</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($revenue, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 font-bold mt-1">Total Paid Invoices</p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11"></path></svg>
                </div>
            </div>

            <!-- Expense This Month -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-rose-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">Pengeluaran Bulan Ini</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Rp {{ number_format($expense, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 font-bold mt-1">Total Bank Withdrawals</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <!-- Profit This Month -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-blue-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-1">Estimasi Laba (Net)</p>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white {{ $profit < 0 ? 'text-rose-600' : '' }}">Rp {{ number_format($profit, 0, ',', '.') }}</h3>
                    <p class="text-[10px] text-gray-400 font-bold mt-1">Income - Expense</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
        </div>
        @endif

        <!-- PSB (New Installations) Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- PSB Today -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-indigo-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">PSB Hari Ini</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($psbToday) }}</h3>
                </div>
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
            </div>

            <!-- PSB This Month -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-emerald-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">PSB Bulan Ini</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($psbMonth) }}</h3>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>

            <!-- PSB This Year -->
            <div class="glass-card p-6 rounded-[2rem] flex items-center justify-between border-l-4 border-purple-500 transition-all hover:shadow-lg hover:scale-[1.01]">
                <div>
                    <p class="text-[10px] font-black text-purple-500 uppercase tracking-widest mb-1">PSB Tahun Ini</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white">{{ number_format($psbYear) }}</h3>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Left Side: Network Analytics -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Live Traffic Analysis Card -->
                <div class="glass-card rounded-[2.5rem] overflow-hidden border border-white/40 dark:border-gray-700/50">
                    <div class="px-8 py-8 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-white/30 dark:bg-gray-900/20">
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Real-time Traffic Metrics</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Monitoring bandwidth utilization for the last 24 hours</p>
                        </div>
                        <div class="flex items-center space-x-3">
                             <div class="hidden md:flex px-4 py-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl items-center mr-2">
                                <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse mr-2"></span>
                                <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-widest">Live Metrics</span>
                             </div>

                             @if(auth()->user()->isAdmin())
                             <div class="flex items-center space-x-2">
                                 <form action="{{ route('radius.clear-stale') }}" method="POST" onsubmit="return confirm('Clean idle sessions (>2h)?')">
                                     @csrf
                                     <button type="submit" class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-amber-600 hover:text-white transition-all border border-amber-100 dark:border-amber-800/30 shadow-sm">
                                         Clear Stale
                                     </button>
                                 </form>
                                 <form action="{{ route('radius.disconnect-all') }}" method="POST" onsubmit="return confirm('Disconnect ALL sessions?')">
                                     @csrf
                                     <button type="submit" class="px-4 py-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-rose-600 hover:text-white transition-all border border-rose-100 dark:border-rose-800/30 shadow-sm">
                                         Disconnect All
                                     </button>
                                 </form>
                             </div>
                             @endif
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
                            <div class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Aggregate Up</p>
                                <p class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                    @if($totalUpload > 1073741824)
                                        {{ number_format($totalUpload / 1073741824, 2) }} <span class="text-xs font-bold opacity-40">GB</span>
                                    @else
                                        {{ number_format($totalUpload / 1048576, 1) }} <span class="text-xs font-bold opacity-40">MB</span>
                                    @endif
                                </p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Aggregate Down</p>
                                <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                                    @if($totalDownload > 1073741824)
                                        {{ number_format($totalDownload / 1073741824, 2) }} <span class="text-xs font-bold opacity-40">GB</span>
                                    @else
                                        {{ number_format($totalDownload / 1048576, 1) }} <span class="text-xs font-bold opacity-40">MB</span>
                                    @endif
                                </p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Auth Success</p>
                                <p class="text-xl font-black text-blue-600 dark:text-blue-400">{{ number_format($authAcceptToday) }}</p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Auth Failed</p>
                                <p class="text-xl font-black text-rose-600 dark:text-rose-400">{{ number_format($authRejectToday) }}</p>
                            </div>
                        </div>

                        <div class="chart-container relative h-[350px]">
                            <canvas id="trafficChartMain"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Consumers & Activity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <!-- Top Users -->
                     <div class="glass-card rounded-[2.5rem] p-8">
                         <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center">
                            <span class="w-1.5 h-6 bg-amber-500 rounded-full mr-3"></span>
                            Heavy Consumers
                         </h3>
                         <div class="space-y-4">
                            @forelse($topUsers as $idx => $user)
                            <div class="flex items-center justify-between p-4 rounded-3xl bg-white/40 dark:bg-gray-900/40 border border-white/60 dark:border-gray-800/60 hover:scale-[1.02] transition-all">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center font-black text-xs text-gray-500">
                                        #{{ $idx + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white font-mono text-sm">{{ $user->username }}</p>
                                        <p class="text-[10px] text-gray-400 font-medium">{{ $user->framedipaddress }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                        {{ $user->total_traffic > 1073741824 ? number_format($user->total_traffic / 1073741824, 2) . ' GB' : number_format($user->total_traffic / 1048576, 1) . ' MB' }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">{{ gmdate('H:i', $user->acctsessiontime) }} Session</p>
                                </div>
                            </div>
                            @empty
                            <div class="py-12 text-center text-gray-400 italic">No heavy usage detected.</div>
                            @endforelse
                         </div>
                     </div>

                     <!-- Mini Activity Feed -->
                     <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center">
                                <span class="w-1.5 h-6 bg-indigo-500 rounded-full mr-3"></span>
                                Recent Pulse
                            </h3>
                            <a href="{{ route('activity-logs.index') }}" class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                        <div class="space-y-6">
                            @foreach($latestActivities->take(5) as $activity)
                            <div class="flex space-x-4 relative">
                                @if(!$loop->last)
                                <div class="absolute left-2.5 top-6 bottom-0 w-px bg-gray-100 dark:bg-gray-800"></div>
                                @endif
                                <div class="w-5 h-5 rounded-full mt-1 z-10 flex items-center justify-center
                                    {{ $activity->action === 'created' ? 'bg-green-500' : ($activity->action === 'updated' ? 'bg-blue-500' : 'bg-indigo-500') }} border-4 border-white dark:border-gray-800 shadow-sm">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $activity->description }}</p>
                                    <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $activity->created_at->diffForHumans() }} &bull; {{ $activity->user->name ?? 'System' }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                     </div>
                </div>
            </div>

            <!-- Right Side: Quick Actions & Intelligence -->
            <div class="space-y-8">
                <!-- Quick Actions Console -->
                <div class="glass-card rounded-[2.5rem] p-8">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6">Action Console</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <a href="{{ route('customers.create') }}" class="flex items-center p-5 rounded-3xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-1">
                            <div class="p-3 bg-white/20 rounded-2xl mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            </div>
                            <div class="text-left">
                                <p class="font-black tracking-tight">New Subscriber</p>
                                <p class="text-[10px] text-indigo-100 font-bold uppercase tracking-widest">Instant Register</p>
                            </div>
                        </a>
                        
                        @if(!auth()->user()->isKasir())
                        <form action="{{ route('invoices.generate-automated') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl mr-4 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-black text-gray-900 dark:text-white tracking-tight">Generate Billing</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Automated Cycle</p>
                                </div>
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('tickets.index') }}" class="flex items-center p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all group">
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-2xl mr-4 group-hover:bg-amber-600 group-hover:text-white transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                            </div>
                            <div class="text-left">
                                <p class="font-black text-gray-900 dark:text-white tracking-tight">Support Desk</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Active Tickets</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Session Intelligence -->
                @if(auth()->user()->isAdmin())
                <div class="glass-card rounded-[2.5rem] p-8 bg-gradient-to-br from-indigo-600 to-purple-700 text-white">
                    <h3 class="text-xl font-black mb-6">Network Health</h3>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold opacity-80">Online Ratio</p>
                            <p class="text-lg font-black">{{ number_format(($onlineNow / max($totalSubscribers, 1)) * 100, 1) }}%</p>
                        </div>
                        <div class="w-full bg-white/10 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full bg-white rounded-full" style="width: {{ ($onlineNow / max($totalSubscribers, 1)) * 100 }}%"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mt-8">
                             <form action="{{ route('radius.clear-stale') }}" method="POST" onsubmit="return confirm('Clean idle sessions (>2h)?')">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-white/10 hover:bg-white/20 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                    Clear Stale
                                </button>
                             </form>
                             <form action="{{ route('radius.disconnect-all') }}" method="POST" onsubmit="return confirm('Disconnect ALL sessions?')">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-white/10 hover:bg-white/20 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                    Flush All
                                </button>
                             </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Live Intelligence Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('trafficChartMain');
            if (!ctx) return;

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
            const textColor = isDark ? '#9ca3af' : '#6b7280';

            const upGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
            upGradient.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
            upGradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

            const downGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 350);
            downGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
            downGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: 'Upload (MB)',
                            data: @json($chartUpload),
                            borderColor: '#6366f1',
                            backgroundColor: upGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.45,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#6366f1',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3,
                        },
                        {
                            label: 'Download (MB)',
                            data: @json($chartDownload),
                            borderColor: '#10b981',
                            backgroundColor: downGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.45,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#10b981',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: '700' },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1f2937' : '#fff',
                            titleColor: isDark ? '#fff' : '#111',
                            bodyColor: isDark ? '#9ca3af' : '#6b7280',
                            borderWidth: 1,
                            borderColor: gridColor,
                            padding: 15,
                            displayColors: true,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)} MB`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor, font: { size: 10, weight: '600' } }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: { 
                                color: textColor, 
                                font: { size: 10, weight: '600' },
                                callback: (val) => val + ' MB'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            // Premium Live Clock
            function updateClock() {
                const clock = document.getElementById('live-clock-detailed');
                if (clock) {
                    const now = new Date();
                    clock.innerText = now.toLocaleTimeString('id-ID', { hour12: false });
                }
            }
            setInterval(updateClock, 1000);
            updateClock();
        });
    </script>
</x-app-layout>
