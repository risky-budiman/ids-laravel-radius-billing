<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Login (Email / Customer ID) -->
        <div>
            <label for="login" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-2 ml-1">Email / ID Pelanggan / Username</label>
            <div class="flex items-center bg-gray-50/50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 transition-all group overflow-hidden">
                <div class="pl-5 pr-4 py-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors bg-gray-50 dark:bg-gray-900/50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-800 group-focus-within:bg-indigo-500/50 transition-colors"></div>
                <input id="login" 
                       class="block w-full px-5 py-4 bg-transparent border-none text-gray-900 dark:text-white focus:ring-0 outline-none text-sm font-semibold placeholder:text-gray-400 dark:placeholder:text-gray-600" 
                       type="text" 
                       name="login" 
                       :value="old('login')" 
                       placeholder="Email atau ID Pelanggan"
                       required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-2 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex justify-between items-center mb-2 ml-1">
                <label for="password" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="flex items-center bg-gray-50/50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 transition-all group overflow-hidden">
                <div class="pl-5 pr-4 py-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors bg-gray-50 dark:bg-gray-900/50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-800 group-focus-within:bg-indigo-500/50 transition-colors"></div>
                <input id="password" 
                       class="block w-full px-5 py-4 bg-transparent border-none text-gray-900 dark:text-white focus:ring-0 outline-none text-sm font-semibold placeholder:text-gray-400 dark:placeholder:text-gray-600" 
                       type="password"
                       name="password"
                       placeholder="••••••••"
                       required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-800 text-indigo-600 shadow-sm focus:ring-indigo-500 transition-colors cursor-pointer" name="remember">
            <label for="remember_me" class="ms-3 text-sm font-medium text-gray-500 dark:text-gray-400 cursor-pointer">Keep me signed in</label>
        </div>

        <div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-2xl shadow-xl shadow-indigo-600/20 transform active:scale-[0.98] transition-all flex items-center justify-center space-x-3">
                <span>Sign in to Dashboard</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>

        @if (Route::has('register'))
            <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                Management Access Restricted.
            </p>
        @endif
    </form>
</x-guest-layout>
