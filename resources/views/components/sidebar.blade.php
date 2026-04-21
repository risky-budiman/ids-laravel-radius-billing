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
            <div class="w-10 h-10 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/30">
                R
            </div>
            <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-400">Radius ISP</span>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-4 pb-20">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
            </li>
            
            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Management</p>
            </li>
            
            <li>
                <a href="{{ route('customers.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('customers.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Subscribers
                </a>
            </li>
            
            <li>
                <a href="{{ route('packages.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('packages.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Packages
                </a>
            </li>
            
            <li>
                <a href="{{ route('nas.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('nas.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    Routers (NAS)
                </a>
            </li>

            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Billing & Status</p>
            </li>
            
            <li>
                <a href="{{ route('invoices.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('invoices.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Invoices
                </a>
            </li>
            
            <li>
                <a href="{{ route('online-users.index') }}" class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('online-users.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }} group">
                    <svg class="w-5 h-5 mr-3 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    Online Users
                </a>
            </li>

            <li class="pt-4 pb-2">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-4">Inventory & Assets</p>
            </li>

            <li>
                <div x-data="{ open: {{ request()->routeIs('inventory.*') || request()->routeIs('suppliers.*') ? 'true' : 'false' }} }">
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
                        
                        <a href="{{ route('inventory.categories') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.categories') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            Categories
                        </a>

                        <a href="{{ route('inventory.stock-in') }}" class="block px-3 py-2 text-sm rounded-lg transition-colors {{ request()->routeIs('inventory.stock-in') ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800' }}">
                            + Add Stock In
                        </a>
                    </div>
                </div>
            </li>

            <li class="pt-4 mt-2 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Settings</p>
                
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
                    </div>
                </div>
            </li>

            <li class="pt-2 mt-2">
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
                    </div>
                </div>
            </li>
        </ul>
    </div>
</aside>
