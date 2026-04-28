<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <title>Portal Pelanggan | {{ get_setting('company_name', 'Radius ISP') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-mesh {
            background-color: #ffffff;
            background-image: 
                radial-gradient(at 0% 0%, hsla(242, 73%, 95%, 1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(242, 73%, 95%, 1) 0, transparent 50%),
                radial-gradient(at 50% 100%, hsla(242, 73%, 98%, 1) 0, transparent 50%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 1);
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animation-delay-2000 { animation-delay: 2s; }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden">
    <!-- Animated Ornaments -->
    <div class="absolute top-0 -left-4 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>

    <div class="w-full max-w-[420px] relative z-10">
        <!-- Branding Area -->
        <div class="text-center mb-8">
            <div class="inline-flex p-4 bg-white rounded-[2rem] shadow-2xl shadow-indigo-500/10 mb-6 border border-white">
                @if(get_setting('company_logo'))
                    <img src="{{ asset('storage/' . get_setting('company_logo')) }}" alt="Logo" class="w-16 h-16 object-contain">
                @else
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/40">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                @endif
            </div>
            <h1 class="text-3xl font-[900] text-slate-900 tracking-tight leading-none mb-2">Portal Pelanggan</h1>
            <p class="text-slate-500 text-xs font-black uppercase tracking-[0.2em]">{{ get_setting('company_name', 'Radius ISP') }}</p>
        </div>

        <!-- Main Card -->
        <div class="login-card rounded-[2.5rem] p-8 sm:p-10 shadow-2xl shadow-indigo-500/5 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-blue-500"></div>
            
            <form action="{{ route('customer.login.post') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- ID Pelanggan Field -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">ID Pelanggan</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <input type="text" name="customer_id" required placeholder="Contoh: ID12345" 
                            class="w-full pl-12 pr-4 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:border-indigo-600 focus:bg-white focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none placeholder:text-slate-300">
                    </div>
                </div>

                <!-- Phone Number Field -->
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nomor HP Terdaftar</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                        <input type="text" name="phone" required placeholder="08xxxxxxxxxx" 
                            class="w-full pl-12 pr-4 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 focus:border-indigo-600 focus:bg-white focus:ring-4 focus:ring-indigo-500/5 transition-all outline-none placeholder:text-slate-300">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-600/20 transform active:scale-[0.98] transition-all flex items-center justify-center space-x-3 text-sm tracking-widest uppercase">
                        <span>MASUK SEKARANG</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>

            @if(session('error') || $errors->any())
            <div class="mt-6 p-4 bg-rose-50 rounded-xl border border-rose-100 flex items-start space-x-3 animate-fade-in">
                <svg class="w-5 h-5 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-rose-600 text-[10px] font-black uppercase tracking-tight leading-tight">
                    {{ session('error') ?: $errors->first() }}
                </p>
            </div>
            @endif
        </div>

        <p class="text-center mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">
            Butuh bantuan? <a href="#" class="text-indigo-600 hover:underline">Hubungi Kami</a>
        </p>
    </div>
</body>
</html>
