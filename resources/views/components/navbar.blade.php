@php
    $isPortal = request()->is('client*') || request()->routeIs('customer.*');
    $user = auth('customer')->user() ?: auth('web')->user();
    $initials = collect(preg_split('/\s+/', trim($user->name ?? 'U')))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $isAdministratorUser = $user instanceof \App\Models\User && $user->isAdministrator();
@endphp

<header class="sticky top-0 z-20 h-16 shrink-0 bg-white/85 dark:bg-surface-900/85 backdrop-blur-md border-b border-surface-200 dark:border-surface-800">
    <div class="flex items-center justify-between gap-3 h-full px-4 sm:px-6">

        {{-- Kiri: menu mobile & pencarian --}}
        <div class="flex items-center gap-2 flex-1 min-w-0" x-data="{
            searchQuery: '',
            searchResults: [],
            isSearching: false,
            mobileSearchOpen: false,
            _ctrl: null,
            search() {
                const q = this.searchQuery.trim();
                if (q.length < 2) { this.searchResults = []; return; }
                this.isSearching = true;
                if (this._ctrl) this._ctrl.abort();
                this._ctrl = new AbortController();
                fetch('{{ route('api.search') }}?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                    signal: this._ctrl.signal,
                })
                    .then(res => res.ok ? res.json() : [])
                    .then(data => { this.searchResults = Array.isArray(data) ? data : []; this.isSearching = false; })
                    .catch(e => { if (e.name !== 'AbortError') { this.searchResults = []; this.isSearching = false; } });
            },
            close() { this.searchQuery = ''; this.searchResults = []; this.mobileSearchOpen = false; },
            focusSearch() { this.mobileSearchOpen = true; this.$nextTick(() => this.$refs.searchInput.focus()); }
        }" @click.outside="close()" @keydown.window.ctrl.k.prevent="focusSearch()" @keydown.window.meta.k.prevent="focusSearch()">

            @unless($isPortal)
                <button type="button" @click="sidebarOpen = true" class="btn-ghost btn-icon lg:hidden" aria-label="Buka menu">
                    <x-icon name="menu" class="w-5 h-5" />
                </button>

                <button type="button" @click="focusSearch()" class="btn-ghost btn-icon sm:hidden" aria-label="Cari">
                    <x-icon name="search" class="w-5 h-5" />
                </button>

                <div class="relative w-full max-w-md hidden sm:block"
                     :class="mobileSearchOpen && '!block fixed inset-x-0 top-16 px-4 py-3 bg-white dark:bg-surface-900 border-b border-surface-200 dark:border-surface-800 shadow-elevated z-50 max-w-none sm:static sm:p-0 sm:bg-transparent sm:border-0 sm:shadow-none sm:max-w-md'">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-surface-400">
                            <x-icon name="search" class="w-4 h-4" x-show="!isSearching" />
                            <svg x-show="isSearching" x-cloak class="w-4 h-4 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        <input type="search" x-ref="searchInput" x-model="searchQuery" @input.debounce.300ms="search" @keydown.escape="close()"
                               class="input !h-9 pl-9 pr-16 bg-surface-50 dark:bg-surface-800/60 border-surface-200 dark:border-surface-700 shadow-none"
                               placeholder="Cari pelanggan, invoice, tiket…" autocomplete="off" aria-label="Pencarian global">
                        <kbd class="hidden sm:flex pointer-events-none absolute inset-y-0 right-2 my-auto h-5 items-center rounded border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900 px-1.5 font-sans text-[10px] font-medium text-surface-400">Ctrl K</kbd>
                    </div>

                    {{-- Hasil pencarian --}}
                    <div x-show="searchQuery.trim().length >= 2" x-cloak x-transition.opacity.duration.150ms
                         class="absolute left-4 right-4 sm:left-0 sm:right-0 mt-2 card shadow-elevated overflow-hidden z-50">
                        <div class="max-h-96 overflow-y-auto py-1">
                            <template x-if="searchResults.length === 0 && !isSearching">
                                <p class="px-4 py-6 text-center text-sm text-surface-500 dark:text-surface-400">
                                    Tidak ada hasil untuk “<span class="font-medium text-surface-700 dark:text-surface-200" x-text="searchQuery"></span>”
                                </p>
                            </template>
                            <template x-for="result in searchResults" :key="result.url">
                                <a :href="result.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-50 dark:hover:bg-surface-800/60 transition-colors">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-surface-100 dark:bg-surface-800 text-surface-500 dark:text-surface-400 shrink-0">
                                        <template x-if="result.icon === 'user'"><x-icon name="user" class="w-4 h-4" /></template>
                                        <template x-if="result.icon === 'bill'"><x-icon name="document" class="w-4 h-4" /></template>
                                        <template x-if="result.icon === 'ticket'"><x-icon name="ticket" class="w-4 h-4" /></template>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-medium text-surface-900 dark:text-white truncate" x-text="result.title"></span>
                                        <span class="block text-xs text-surface-500 dark:text-surface-400 truncate" x-text="result.subtitle"></span>
                                    </span>
                                    <span class="badge-gray shrink-0" x-text="result.type"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        {{-- Kanan: tema, notifikasi, profil --}}
        <div class="flex items-center gap-1 sm:gap-2" x-data="{
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

            {{-- Tema --}}
            <div class="relative">
                <button type="button" @click="themeMenuOpen = !themeMenuOpen" @click.outside="themeMenuOpen = false" class="btn-ghost btn-icon" aria-label="Ganti tema">
                    <x-icon name="sun" class="w-5 h-5" x-show="theme === 'light'" />
                    <x-icon name="moon" class="w-5 h-5" x-show="theme === 'dark'" x-cloak />
                    <x-icon name="monitor" class="w-5 h-5" x-show="theme === 'system'" x-cloak />
                </button>
                <div x-show="themeMenuOpen" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-30 w-36 mt-2 p-1 card shadow-elevated">
                    @foreach(['light' => ['sun', 'Terang'], 'dark' => ['moon', 'Gelap'], 'system' => ['monitor', 'Sistem']] as $val => [$ico, $lbl])
                        <button type="button" @click="setTheme('{{ $val }}')"
                                class="flex items-center gap-2.5 w-full px-2.5 py-1.5 rounded-md text-sm text-surface-700 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800"
                                :class="theme === '{{ $val }}' && '!text-primary-600 dark:!text-primary-400 font-medium'">
                            <x-icon :name="$ico" class="w-4 h-4" /> {{ $lbl }}
                        </button>
                    @endforeach
                </div>
            </div>

            @unless($isPortal)
            {{-- Notifikasi --}}
            <div class="relative" x-data="{
                notifications: {},
                unreadCount: 0,
                isOpen: false,
                lastAlertTime: 0,
                needPermission: ('Notification' in window && Notification.permission !== 'granted'),
                requestSoundPermission() {
                    if (typeof window.requestDesktopNotificationPermission === 'function') {
                        window.requestDesktopNotificationPermission();
                    } else if (typeof window.playNotificationChime === 'function') {
                        window.playNotificationChime();
                    }
                    if ('Notification' in window) {
                        Notification.requestPermission().then(perm => { this.needPermission = (perm !== 'granted'); });
                    }
                },
                showToast(msg) {
                    if (msg && typeof window.showGlobalToast === 'function') window.showGlobalToast(msg);
                },
                triggerAlert(msg) {
                    const now = Date.now();
                    if (now - this.lastAlertTime < 1500) return; // cegah notifikasi ganda
                    this.lastAlertTime = now;
                    this.fetchNotifications();
                    if (msg) {
                        this.showToast(msg);
                        if ('Notification' in window && Notification.permission === 'granted') {
                            const notifTitle = '{{ addslashes(get_setting('company_name', 'Radius ISP')) }} - Notifikasi';
                            const notifOptions = {
                                body: msg,
                                icon: '{{ asset('favicon.png') }}',
                                badge: '{{ asset('favicon.png') }}',
                                tag: 'ticket-notif-' + Date.now(),
                                renotify: true,
                                silent: false
                            };
                            if ('serviceWorker' in navigator) {
                                navigator.serviceWorker.ready.then(reg => reg.showNotification(notifTitle, notifOptions))
                                    .catch(() => { try { new Notification(notifTitle, notifOptions); } catch(e) {} });
                            } else {
                                try { new Notification(notifTitle, notifOptions); } catch(e) {}
                            }
                        }
                    } else {
                        this.playNotificationSound();
                    }
                },
                fetchNotifications() {
                    fetch('{{ route('api.notifications') }}', { headers: { 'Accept': 'application/json' } })
                        .then(res => res.json())
                        .then(data => {
                            this.notifications = data;
                            this.unreadCount = data['Belum Dibaca'] ? data['Belum Dibaca'].length : 0;
                        })
                        .catch(err => console.error('Notification fetch error:', err));
                },
                initWebSocket() {
                    const setup = () => {
                        if (!window.Echo) { setTimeout(setup, 300); return; }
                        const userId = {{ auth()->id() ?? 'null' }};
                        if (!userId) return;
                        window.Echo.private(`App.Models.User.${userId}`)
                            .notification((notification) => this.triggerAlert(notification.message || 'Notifikasi Baru'));
                        window.Echo.private('admin.tickets')
                            .listen('.ticket.created', (e) => {
                                const num = e.ticket ? e.ticket.ticket_number : '';
                                const sub = e.ticket ? e.ticket.subject : 'Tiket Baru';
                                this.triggerAlert(`Tiket baru #${num} — ${sub}`);
                            })
                            .listen('.ticket.reply.created', (e) => {
                                const num = e.reply ? e.reply.ticket_number : '';
                                const msg = e.reply ? e.reply.message : 'Balasan Baru';
                                this.triggerAlert(`Balasan tiket #${num}: ${msg}`);
                            })
                            .listen('.ticket.updated', () => this.fetchNotifications());
                    };
                    setup();
                },
                playNotificationSound() {
                    if (typeof window.playNotificationChime === 'function') window.playNotificationChime();
                },
                testRealtime() {
                    fetch('{{ route('admin.test-realtime') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    }).then(r => r.json()).catch(e => console.error('Test error:', e));
                },
                markAllRead() {
                    fetch('{{ route('api.notifications.read') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    }).then(() => { this.notifications = {}; this.unreadCount = 0; this.isOpen = false; });
                },
                markAsRead(id) {
                    fetch('{{ route('api.notifications.read') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ id: id })
                    }).then(() => this.fetchNotifications());
                }
            }" x-init="fetchNotifications(); initWebSocket();">
                <button type="button" @click="isOpen = !isOpen" class="btn-ghost btn-icon relative" aria-label="Notifikasi">
                    <x-icon name="bell" class="w-5 h-5" />
                    <span x-show="unreadCount > 0" x-cloak x-text="unreadCount > 99 ? '99+' : unreadCount"
                          class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-white text-[10px] font-semibold leading-4 text-center ring-2 ring-white dark:ring-surface-900"></span>
                </button>

                <div x-show="isOpen" x-cloak @click.outside="isOpen = false" x-transition.opacity.duration.150ms
                     class="absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-2rem)] card shadow-elevated overflow-hidden z-50">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-surface-200 dark:border-surface-800">
                        <p class="text-sm font-semibold text-surface-900 dark:text-white">Notifikasi</p>
                        <div class="flex items-center gap-1">
                            @if($isAdministratorUser)
                                <button type="button" @click.stop="testRealtime" class="btn-ghost btn-sm !h-7" title="Kirim notifikasi uji">Tes</button>
                            @endif
                            <button type="button" @click="markAllRead" class="btn-ghost btn-sm !h-7 !text-primary-600 dark:!text-primary-400">Tandai semua dibaca</button>
                        </div>
                    </div>

                    <div x-show="needPermission" x-cloak class="flex items-center justify-between gap-3 px-4 py-2.5 bg-amber-50 dark:bg-amber-500/10 border-b border-amber-200/70 dark:border-amber-500/20">
                        <span class="text-xs text-amber-800 dark:text-amber-300">Aktifkan suara & notifikasi desktop</span>
                        <button type="button" @click.stop="requestSoundPermission" class="btn btn-sm !h-7 bg-amber-500 text-white hover:bg-amber-600">Aktifkan</button>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <template x-if="unreadCount === 0 && Object.keys(notifications).length === 0">
                            <div class="px-6 py-10 text-center">
                                <div class="w-10 h-10 mx-auto mb-3 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center text-surface-400">
                                    <x-icon name="inbox" class="w-5 h-5" />
                                </div>
                                <p class="text-sm font-medium text-surface-700 dark:text-surface-200">Semua sudah dibaca</p>
                                <p class="text-xs text-surface-500 dark:text-surface-400 mt-0.5">Notifikasi baru akan muncul di sini.</p>
                            </div>
                        </template>

                        <template x-for="(items, group) in notifications" :key="group">
                            <div>
                                <div class="px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-surface-500 dark:text-surface-400 bg-surface-50 dark:bg-surface-800/50" x-text="group"></div>
                                <template x-for="notif in items" :key="notif.id">
                                    <div @click="if(notif.data.url) { markAsRead(notif.id); window.location.href = notif.data.url; }"
                                         class="flex items-start gap-3 px-4 py-3 border-b last:border-0 border-surface-100 dark:border-surface-800 hover:bg-surface-50 dark:hover:bg-surface-800/40 cursor-pointer transition-colors">
                                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="notif.read_at ? 'bg-transparent' : 'bg-primary-500'"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm leading-snug" :class="notif.read_at ? 'text-surface-500 dark:text-surface-400' : 'text-surface-900 dark:text-surface-100'" x-text="notif.data.message"></p>
                                            <p class="text-xs text-surface-400 mt-1" x-text="new Date(notif.created_at).toLocaleString('id-ID')"></p>
                                        </div>
                                        <template x-if="!notif.read_at">
                                            <button type="button" @click.stop="markAsRead(notif.id)" class="p-1 rounded text-surface-400 hover:text-primary-600" title="Tandai sudah dibaca">
                                                <x-icon name="check" class="w-4 h-4" />
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            @endunless

            <div class="hidden sm:block w-px h-6 mx-1 bg-surface-200 dark:bg-surface-800"></div>

            {{-- Profil --}}
            <x-dropdown align="right" width="56">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">
                        <span class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-semibold">{{ $initials ?: 'U' }}</span>
                        <span class="hidden md:block text-sm font-medium text-surface-700 dark:text-surface-200 max-w-[10rem] truncate">{{ $user->name ?? 'User' }}</span>
                        <x-icon name="chevron-down" class="hidden md:block w-4 h-4 text-surface-400" />
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-3 border-b border-surface-200 dark:border-surface-800">
                        <p class="text-sm font-medium text-surface-900 dark:text-white truncate">{{ $user->name ?? 'User' }}</p>
                        <p class="text-xs text-surface-500 dark:text-surface-400 truncate">{{ $user->email ?? $user->customer_code ?? '' }}</p>
                    </div>
                    <div class="py-1">
                        @unless($isPortal)
                            <x-dropdown-link :href="route('profile.edit')">
                                <x-icon name="user" class="w-4 h-4 text-surface-400" /> Profil Saya
                            </x-dropdown-link>
                            @if($isAdministratorUser)
                                <x-dropdown-link :href="route('settings.company')">
                                    <x-icon name="office" class="w-4 h-4 text-surface-400" /> Profil Perusahaan
                                </x-dropdown-link>
                            @endif
                        @endunless
                    </div>
                    <div class="py-1 border-t border-surface-200 dark:border-surface-800">
                        <form method="POST" action="{{ $user && $user->isCustomer() ? route('customer.logout') : route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                                <x-icon name="logout" class="w-4 h-4" /> Keluar
                            </button>
                        </form>
                    </div>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
