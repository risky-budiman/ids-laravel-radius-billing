<!-- Sidebar Backdrop for Mobile -->
<div x-show="sidebarOpen" 
     class="fixed inset-0 z-20 bg-black/50 lg:hidden transition-opacity" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"></div>

<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col h-screen"
       :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
    
    <!-- Logo -->
    <div class="flex items-center justify-center h-20 shrink-0 border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center space-x-3">
            @php 
                $logo = get_setting('app_icon') ?: get_setting('company_logo');
            @endphp
            @if($logo)
                <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="w-10 h-10 object-contain rounded-lg">
            @else
                <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/30">
                    {{ substr(get_setting('company_name', 'Radius'), 0, 1) }}
                </div>
            @endif
            <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400">
                {{ get_setting('company_name', 'Radius ISP') }}
            </span>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-4 pb-20">
        @php 
            $isPortal = request()->is('client*') || request()->routeIs('customer.*');
            $user = auth('customer')->user() ?: auth('web')->user(); 
        @endphp
        <ul class="space-y-2">
            @if($isPortal)
            <!-- CUSTOMER PORTAL MENU -->
            <li>
                <a href="{{ route('customer.dashboard') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customer.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard Portal
                </a>
            </li>
            <li>
                <a href="{{ route('customer.invoices') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customer.invoices') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Tagihan Saya
                </a>
            </li>
            <li>
                <a href="{{ route('customer.boosters') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customer.boosters') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Booster & Add-on
                </a>
            </li>
            <li>
                <a href="{{ route('customer.tickets.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customer.tickets.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Lapor Gangguan
                </a>
            </li>
            @else
            <!-- ADMIN & STAFF MENU -->
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
            </li>

            <!-- Network Operations Section -->
            @if(!$isPortal && (auth()->user()->isTeknisi() || auth()->user()->isAdministrator()))
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Network Operations</p>
            </li>
            <li>
                <div x-data="{ open: {{ request()->routeIs('noc.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                            NOC Center
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" class="mt-1 space-y-1 pl-11 pr-4">
                        <a href="{{ route('noc.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('noc.index') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Network Overview
                        </a>
                        <a href="{{ route('noc.signals') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('noc.signals') || request()->routeIs('noc.history') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Optical Signals
                        </a>
                        <a href="{{ route('noc.discovery') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('noc.discovery') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Mass Discovery
                        </a>
                    </div>
                </div>
            </li>
            <li>
                <a href="{{ route('olts.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('olts.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    OLT Master
                </a>
            </li>
            <li>
                <a href="{{ route('acs-servers.devices') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('acs-servers.devices') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    GenieACS
                </a>
            </li>
            @endif
            
            @if(!$isPortal && (auth()->user()->isAdmin() || auth()->user()->isSales() || auth()->user()->isTeknisi() || auth()->user()->isAdministrator()))
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Management</p>
            </li>
            @endif

            @if(auth()->user()->isSales())
            <li>
                <a href="{{ route('sales.dashboard') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('sales.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Sales Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('sales.customers') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('sales.customers') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    My Referrals
                </a>
            </li>
            <li>
                <a href="{{ route('sales.ledger') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('sales.ledger') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11.401M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    My Wallet / Ledger
                </a>
            </li>
            @endif
            
            @if(auth()->user()->isAdministrator() || auth()->user()->isAdmin() || auth()->user()->isSales() || auth()->user()->isTeknisi())
            <li>
                <a href="{{ route('customers.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customers.index', 'customers.create', 'customers.edit', 'customers.show') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Subscribers
                </a>
            </li>
            @endif

            @if(get_setting('enable_partner_module') == '1' && (auth()->user()->isAdministrator() || auth()->user()->isAdmin() || auth()->user()->isMitra()))
            <li>
                <a href="{{ auth()->user()->isMitra() ? route('partners.show', auth()->id()) : route('partners.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('partners.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Partners & Resellers
                </a>
            </li>
            @endif
            
            @if(auth()->user()->isAdministrator())
            <li>
                <a href="{{ route('packages.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('packages.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Packages
                </a>
            </li>
            @endif
            
            @if(auth()->user()->isAdministrator())
            <li>
                <a href="{{ route('nas.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('nas.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    Routers (NAS)
                </a>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi() || auth()->user()->isSales())
            <li>
                <a href="{{ route('tickets.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('tickets.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    Tickets
                </a>
            </li>
            @endif



            @if(!$isPortal && (auth()->user()->isAdmin() || auth()->user()->isKasir() || auth()->user()->isTeknisi()))
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Billing & Status</p>
            </li>
            @endif
            
            @if(auth()->user()->isAdmin() || auth()->user()->isKasir())
            <li>
                <a href="{{ route('invoices.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('invoices.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Invoices
                </a>
            </li>
            <li>
                <a href="{{ route('invoice-templates.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('invoice-templates.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                    Invoice Builder
                </a>
            </li>
            @endif
            
            @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi())
            <li>
                <a href="{{ route('online-users.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('online-users.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    Online Users
                </a>
            </li>
            <li>
                <a href="{{ route('auth-logs.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('auth-logs.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Auth Logs
                </a>
            </li>
            @endif

            @if(!$isPortal && (auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isAdministrator() || auth()->user()->isKasir())))
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Finance & Accounting</p>
            </li>
            <li>
                <a href="{{ route('bank-accounts.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('bank-accounts.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Bank Accounts (Kas/Bank)
                </a>
            </li>
            <li>
                <a href="{{ route('accounting.coa.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('accounting.coa.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Chart of Accounts
                </a>
            </li>
            <li>
                <div x-data="{ open: {{ request()->routeIs('accounting.reports.*', 'accounting.journals.*', 'accounting.tax-settings.*', 'accounting.closing.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m32-2v2a4 4 0 00-4-4h-1a4 4 0 00-4 4v2m-9-3h.01M12 12h.01M12 9h.01M12 6h.01M11 12h.01M12 12h.01M12 12h.01"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                            Finance Reports
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" class="mt-1 space-y-1 pl-11 pr-4">
                        <a href="{{ route('accounting.journals.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.journals.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            General Ledger
                        </a>
                        <a href="{{ route('accounting.reports.profit-loss') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.reports.profit-loss') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Profit & Loss
                        </a>
                        <a href="{{ route('accounting.reports.balance-sheet') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.reports.balance-sheet') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Balance Sheet
                        </a>
                        <a href="{{ route('accounting.reports.cash-flow') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.reports.cash-flow') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Cash Flow
                        </a>
                        <a href="{{ route('accounting.taxes.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.taxes.*') || request()->routeIs('accounting.reports.tax-summary') || request()->routeIs('accounting.tax-settings*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Pusat Pajak PPN
                        </a>
                        <a href="{{ route('accounting.closing.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('accounting.closing*') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Closing Period
                        </a>
                    </div>
                </div>
            </li>
            @if(auth()->user()->isAdministrator() || auth()->user()->isAdmin())
            <li>
                <a href="{{ route('sales-commissions.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('sales-commissions.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1H11.401M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Sales Force Ledger
                </a>
            </li>
            @endif
            @endif

            @if(!$isPortal && (auth()->user() && (auth()->user()->isAdmin() || auth()->user()->isTeknisi())))
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Inventory & Assets</p>
            </li>

            <li>
                <div x-data="{ open: {{ request()->routeIs('inventory.*') || request()->routeIs('suppliers.*') || request()->routeIs('purchase-orders.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Warehouse
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 space-y-1 pl-11 pr-4" style="display: none;">
                        
                        <a href="{{ route('inventory.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.show') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Product Stock
                        </a>
                        
                        <a href="{{ route('suppliers.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Suppliers
                        </a>

                        <a href="{{ route('purchase-orders.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('purchase-orders.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Purchase Orders
                        </a>
                        
                        <a href="{{ route('inventory.categories') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.categories') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Categories
                        </a>

                        <a href="{{ route('inventory.stock-in') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.stock-in') ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            + Add Stock In
                        </a>

                        <a href="{{ route('inventory.stock-out') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.stock-out') ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            - Issue Item (Out)
                        </a>

                        <a href="{{ route('inventory.outflow') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.outflow') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Audit Outflow
                        </a>
                    </div>
                </div>
            </li>
            
            <li>
                <a href="{{ route('fixed-assets.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('fixed-assets.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Fixed Assets
                </a>
            </li>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeknisi() || auth()->user()->isSales())
            <li>
                <a href="{{ route('customers.map') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customers.map') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Customer Maps
                </a>
            </li>
            @endif
            @endif

            @if(!$isPortal && auth()->user() && auth()->user()->isAdministrator())
            <li class="pt-4 mt-2 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Settings & Security</p>
                
                <li>
                    <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                        <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Manage Staff
                    </a>
                </li>

                <div x-data="{ open: {{ request()->routeIs('locations.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Data Master
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 space-y-1 pl-11 pr-4" style="display: none;">
                        
                        <a href="{{ route('locations.regions') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('locations.regions') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Regional Data
                        </a>
                        
                        <a href="{{ route('locations.stos') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('locations.stos') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            STO Data
                        </a>
                        
                        <a href="{{ route('locations.stbs') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('locations.stbs') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            STB Data
                        </a>

                        <a href="{{ route('locations.odcs') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('locations.odcs') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            ODC Data
                        </a>

                        <a href="{{ route('locations.odps') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('locations.odps') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            ODP Data
                        </a>
 
                        <a href="{{ route('settings.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('settings.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                System Settings
                            </div>
                        </a>
                    </div>
                </div>
            </li>

            <li class="pt-2">
                <div x-data="{ open: {{ request()->routeIs('integrations.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Integrations
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mt-1 space-y-1 pl-11 pr-4" style="display: none;">
                        
                        <a href="{{ route('integrations.payment') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('integrations.payment') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Payment Gateways
                        </a>
                        
                        <a href="{{ route('integrations.whatsapp') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('integrations.whatsapp') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            WhatsApp Gateway
                        </a>

                        <a href="{{ route('acs-servers.index') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('acs-servers.index') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            GenieACS Servers
                        </a>

                        <a href="{{ route('integrations.noc-bot') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('integrations.noc-bot') ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            NOC Bot Integration
                        </a>
                    </div>
                </div>
            </li>

            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">System Audit</p>
            </li>
 
            <li>
                <a href="{{ route('activity-logs.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('activity-logs.*') ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Activity Logs
                </a>
            </li>
            <li>
                <a href="{{ route('server-logs.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('server-logs.*') ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Server Logs
                </a>
            </li>
            <!-- WhatsApp Group -->
            <li class="pt-4 pb-2 border-t border-gray-200/50 dark:border-gray-800/50 mt-2">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-500 uppercase tracking-wider px-4 mb-2">WhatsApp</p>
            </li>
            <li>
                <a href="{{ route('whatsapp-templates.index') }}" class="flex items-center px-4 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('whatsapp-templates.*') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    WA Templates
                </a>
            </li>
            <li>
                <a href="{{ route('whatsapp-broadcast.create') }}" class="flex items-center px-4 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('whatsapp-broadcast.*') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    Broadcast Pesan
                </a>
            </li>
            <li>
                <a href="{{ route('whatsapp-logs.index') }}" class="flex items-center px-4 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('whatsapp-logs.*') ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Riwayat Pesan
                </a>
            </li>
            @endif

            @if(!$isPortal)
            <li class="pt-4 pb-2 border-t border-gray-200/50 dark:border-gray-800/50">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Application</p>
            </li>

            <li>
                <a href="{{ route('changelog.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('changelog.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.168.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    App Changelog
                </a>
            </li>
            @endif
            @endif
        </ul>
    </div>
</aside>
