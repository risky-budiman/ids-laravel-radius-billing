<x-guest-layout>
    <div class="mb-8 text-sm text-gray-500 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 font-medium">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-2 ml-1">Email Address</label>
            <div class="flex items-center bg-gray-50/50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 transition-all group overflow-hidden">
                <div class="pl-5 pr-4 py-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors bg-gray-50 dark:bg-gray-900/50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-800 group-focus-within:bg-indigo-500/50 transition-colors"></div>
                <input id="email" 
                       class="block w-full px-5 py-4 bg-transparent border-none text-gray-900 dark:text-white focus:ring-0 outline-none text-sm font-semibold placeholder:text-gray-400 dark:placeholder:text-gray-600" 
                       type="email" 
                       name="email" 
                       :value="old('email')" 
                       placeholder="Enter your email"
                       required autofocus />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs" />
        </div>

        <div class="flex flex-col space-y-4 pt-2">
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-2xl shadow-xl shadow-indigo-600/20 transform active:scale-[0.98] transition-all flex items-center justify-center space-x-3">
                <span>Email Password Reset Link</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.66l7-4.67a2 2 0 012.22 0l7 4.67a2 2 0 01.89 1.66V19a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
            </button>

            <a href="{{ route('login') }}" class="py-4 text-center text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center group transition-colors">
                <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Login
            </a>
        </div>
    </form>
</x-guest-layout>
