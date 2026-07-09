<!-- Navbar -->
<header class="sticky top-0 z-20 glass border-b border-gray-200/50 dark:border-gray-800/50 backdrop-blur-xl">
    <div class="flex items-center justify-between px-6 py-4">
        
        <!-- Left Side: Hamburger & Search -->
        @php 
            $isPortal = request()->is('client*') || request()->routeIs('customer.*');
            $user = auth('customer')->user() ?: auth('web')->user(); 
        @endphp
        
        <div class="flex items-center space-x-4" x-data="{ 
            searchQuery: '',
            searchResults: [],
            isSearching: false,
            mobileSearchOpen: false,
            search() {
                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }
                this.isSearching = true;
                fetch(`/api/search?q=${this.searchQuery}`)
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data;
                        this.isSearching = false;
                    });
            }
        }" @click.away="searchQuery = ''; mobileSearchOpen = false">
            <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
            </button>

            @if(!$isPortal)
            <!-- Mobile Search Toggle -->
            <button @click="mobileSearchOpen = !mobileSearchOpen" class="sm:hidden text-gray-500 dark:text-gray-400 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>

            <div class="relative" :class="mobileSearchOpen ? 'fixed inset-x-0 top-16 px-6 py-4 bg-white dark:bg-gray-800 z-50 shadow-xl sm:static sm:p-0 sm:bg-transparent sm:shadow-none block' : 'hidden sm:block'">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 sm:pl-3" :class="mobileSearchOpen ? 'pl-9' : ''">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" x-show="!isSearching" viewBox="0 0 24 24" fill="none"><path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" x-show="isSearching" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
                <input type="text" 
                       x-model="searchQuery" 
                       @input.debounce.300ms="search"
                       class="w-full py-2 pl-10 pr-4 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all sm:w-64 lg:w-80" 
                       placeholder="Search everything...">

                <!-- Search Results Dropdown -->
                <div x-show="searchQuery.length >= 2" 
                     x-transition 
                     class="absolute top-full left-0 w-full mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50"
                     style="display: none;">
                    
                    <div class="max-h-96 overflow-y-auto">
                        <template x-if="searchResults.length === 0 && !isSearching">
                            <div class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">No results found for "<span x-text="searchQuery" class="font-bold"></span>"</div>
                        </template>

                        <template x-for="result in searchResults" :key="result.url">
                            <a :href="result.url" class="flex items-center px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors border-b last:border-0 border-gray-100 dark:border-gray-700">
                                <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 mr-3">
                                    <template x-if="result.icon === 'user'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></template>
                                    <template x-if="result.icon === 'bill'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></template>
                                    <template x-if="result.icon === 'ticket'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg></template>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-0.5" x-text="result.type"></p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="result.title"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="result.subtitle"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @php $user = auth('customer')->user() ?: auth('web')->user(); @endphp
        <!-- Right Side: Theme Switcher, Notifs, Profile -->
        <div class="flex items-center space-x-5" x-data="{ 
                theme: localStorage.getItem('theme') || 'system',
                themeMenuOpen: false,
                setTheme(val) {
                    this.theme = val;
                    localStorage.setItem('theme', val);
                    if (val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    this.themeMenuOpen = false;
                }
            }">
            
            <!-- Theme Switcher Dropdown -->
            <div class="relative">
                <button @click="themeMenuOpen = !themeMenuOpen" @click.away="themeMenuOpen = false" class="p-2 text-gray-500 rounded-full hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors focus:outline-none">
                    <!-- Sun Icon (Light Mode) -->
                    <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <!-- Moon Icon (Dark Mode) -->
                    <svg x-show="theme === 'dark'" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <!-- Monitor Icon (System) -->
                    <svg x-show="theme === 'system'" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </button>

                <!-- Theme Menu -->
                <div x-show="themeMenuOpen" x-transition class="absolute right-0 z-20 w-36 py-2 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700" style="display: none;">
                    <a href="#" @click.prevent="setTheme('light')" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'light' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : ''">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> Light
                    </a>
                    <a href="#" @click.prevent="setTheme('dark')" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'dark' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : ''">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg> Dark
                    </a>
                    <a href="#" @click.prevent="setTheme('system')" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'system' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : ''">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> System
                    </a>
                </div>
            </div>

            @if(!$isPortal)
            <!-- Notifications -->
            <div class="relative" x-data="{ 
                notifications: {},
                unreadCount: 0,
                isOpen: false,
                fetchNotifications() {
                    fetch('/api/notifications')
                        .then(res => res.json())
                        .then(data => {
                            this.notifications = data;
                            this.unreadCount = Object.values(data).reduce((acc, current) => acc + current.length, 0);
                        });
                },
                markAllRead() {
                    fetch('/api/notifications/read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    }).then(() => {
                        this.notifications = {};
                        this.unreadCount = 0;
                        this.isOpen = false;
                    });
                },
                markAsRead(id) {
                    fetch('/api/notifications/read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({ id: id })
                    }).then(() => {
                        this.fetchNotifications();
                    });
                }
            }" x-init="fetchNotifications(); setInterval(() => fetchNotifications(), 30000)">
                <button @click="isOpen = !isOpen" class="p-2 text-gray-500 rounded-full hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors relative focus:outline-none">
                    <span x-show="unreadCount > 0" class="absolute top-1.5 right-1.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full ring-2 ring-white dark:ring-gray-900 flex items-center justify-center" x-text="unreadCount"></span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="isOpen" 
                     @click.away="isOpen = false"
                     x-transition 
                     class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50 py-2"
                     style="display: none;">
                    
                    <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <span class="font-bold text-sm text-gray-900 dark:text-gray-100">Notifications</span>
                        <button @click="markAllRead" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Mark all as read</button>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <template x-if="unreadCount === 0">
                            <div class="p-8 text-center">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">All caught up!</p>
                            </div>
                        </template>

                        <template x-for="(items, group) in notifications" :key="group">
                            <div>
                                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-1.5 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest border-y border-gray-100 dark:border-gray-700" x-text="group"></div>
                                <template x-for="notif in items" :key="notif.id">
                                    <div @click="if(notif.data.url) { markAsRead(notif.id); window.location.href = notif.data.url; }" 
                                         class="flex items-start px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors border-b last:border-0 border-gray-100 dark:border-gray-700 relative group/item cursor-pointer">
                                        <div class="flex-1">
                                            <p class="text-xs text-gray-900 dark:text-gray-100 font-medium" x-text="notif.data.message"></p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1" x-text="new Date(notif.created_at).toLocaleString()"></p>
                                        </div>
                                        <button @click.stop="markAsRead(notif.id)" class="ml-2 p-1 text-gray-400 dark:text-gray-500 hover:text-indigo-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            @endif

            <!-- User Menu -->
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center space-x-2 focus:outline-none">
                        <img class="w-9 h-9 rounded-full object-cover border-2 border-indigo-500/20" src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'User') }}&color=4F46E5&background=EEF2FF" alt="Avatar">
                        <span class="hidden text-sm font-medium text-gray-700 dark:text-gray-300 md:block">{{ $user->name ?? 'User' }}</span>
                        <svg class="hidden w-4 h-4 text-gray-500 dark:text-gray-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    @if(!$isPortal)
                    <x-dropdown-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-dropdown-link>
                    
                    <x-dropdown-link :href="route('settings.company')">
                        {{ __('Company Profile & Settings') }}
                    </x-dropdown-link>
                    @endif

                    <!-- Authentication -->
                    <form method="POST" action="{{ $user && $user->isCustomer() ? route('customer.logout') : route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="$user && $user->isCustomer() ? route('customer.logout') : route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
