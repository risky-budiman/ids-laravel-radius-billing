<x-guest-layout>
    <!-- Header Area -->
    <div class="mb-10 text-center lg:text-left">
        <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-tight">
            Forgot Password?<br>
            <span class="text-blue-600">Recovery Access</span>
        </h2>
        <p class="text-slate-500 text-sm font-medium mt-4 max-w-sm">
            Enter your email and we'll send a password reset link to choose a new one.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Card Wrapper -->
    <div class="bg-white rounded-[2.5rem] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] border border-slate-100 p-8 sm:p-10">
        <div class="mb-8 text-sm text-slate-500 leading-relaxed bg-slate-50 p-6 rounded-2xl border border-slate-100 font-medium">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.') }}
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-8">
            @csrf

            <!-- Email Address -->
            <div class="space-y-3">
                <label for="email" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                <div class="relative flex items-center">
                    <div class="absolute left-5 text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <input id="email" 
                        class="block w-full pl-14 pr-6 py-5 bg-slate-50 border border-slate-100 rounded-2xl text-slate-900 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none text-sm font-bold placeholder:text-slate-300 shadow-inner" 
                        type="email" 
                        name="email" 
                        :value="old('email')" 
                        placeholder="Enter your email"
                        required autofocus />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-[10px] font-bold text-rose-500 uppercase" />
            </div>

            <div class="flex flex-col space-y-6 pt-2">
                <button type="submit" 
                        style="background-color: #4f46e5; background-image: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);"
                        class="w-full text-white font-black py-6 px-6 rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center space-x-4 text-sm uppercase tracking-[0.15em] border-none">
                    <span>Email Password Reset Link</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 19v-8.93a2 2 0 01.89-1.66l7-4.67a2 2 0 012.22 0l7 4.67a2 2 0 01.89 1.66V19a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                </button>

                <a href="{{ route('login') }}" class="group flex items-center justify-center space-x-2 text-xs font-black text-slate-400 hover:text-blue-600 transition-colors uppercase tracking-widest">
                    <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Login</span>
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
