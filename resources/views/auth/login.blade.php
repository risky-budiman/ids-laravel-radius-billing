<x-guest-layout>
    <!-- Header Area -->
    <div class="mb-10 text-center lg:text-left">
        <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
            Access Dashboard<br>
            <span class="text-blue-600">Management ISP</span>
        </h2>
        <p class="text-slate-500 text-sm font-medium mt-4 max-w-sm">
            Control your network infrastructure and customers with precision.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Card Wrapper -->
    <div class="bg-white rounded-[2.5rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] border border-slate-100 p-8 sm:p-10">
        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <!-- Login Field -->
            <div class="space-y-2">
                <label for="login" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Kredensial Akses</label>
                <div class="relative flex items-center">
                    <div class="absolute left-5 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input id="login" 
                        class="block w-full pl-14 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-slate-300 shadow-inner" 
                        type="text" 
                        name="login" 
                        :value="old('login')" 
                        placeholder="Username atau Email"
                        required autofocus />
                </div>
                <x-input-error :messages="$errors->get('login')" class="mt-2 text-[10px] font-bold text-rose-500 uppercase" />
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                    <label for="password" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Kata Sandi</label>
                    @if (Route::has('password.request'))
                        <a class="text-[10px] font-black text-blue-600 hover:text-blue-700 uppercase tracking-widest" href="{{ route('password.request') }}">Lupa?</a>
                    @endif
                </div>
                <div class="relative flex items-center">
                    <div class="absolute left-5 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input id="password" 
                        class="block w-full pl-14 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-slate-300 shadow-inner" 
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required autocomplete="current-password" />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-[10px] font-bold text-rose-500 uppercase" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center py-2">
                <input id="remember_me" type="checkbox" class="w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-500 cursor-pointer transition-all" name="remember">
                <label for="remember_me" class="ms-3 text-xs font-bold text-slate-500 cursor-pointer hover:text-slate-700">Ingat sesi saya</label>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        style="background-color: #4f46e5; background-image: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);"
                        class="w-full text-white font-black py-5 px-6 rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center space-x-4 text-sm uppercase tracking-[0.15em] border-none">
                    <span>Masuk ke Dashboard</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Mobile Footer -->
    <div class="lg:hidden mt-12 text-center pb-8">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] flex items-center justify-center gap-2">
            <span>&copy; {{ date('Y') }} {{ get_setting('company_name') }}</span>
            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-400 font-mono tracking-normal lowercase">v{{ app_version() }}</span>
        </p>
    </div>
</x-guest-layout>
