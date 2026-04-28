<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pelanggan | ISP Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); }
        .bg-pattern { background-image: radial-gradient(#6366f1 0.5px, transparent 0.5px); background-size: 30px 30px; opacity: 0.1; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-pattern"></div>
    <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-500/10 rounded-full blur-[120px]"></div>
    <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] bg-blue-500/10 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-[460px] relative">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-indigo-600/30">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">Portal Pelanggan</h1>
            <p class="text-gray-500 font-medium">Masuk untuk mengelola layanan Anda</p>
        </div>

        <div class="glass bg-white rounded-[2.5rem] p-10 shadow-2xl shadow-indigo-500/5 border border-white/50">
            <form action="{{ route('customer.login.post') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">ID Pelanggan</label>
                    <div class="relative">
                        <input type="text" name="customer_id" required placeholder="Contoh: ID12345" 
                            class="w-full px-6 py-4 bg-gray-50/50 border-2 border-gray-100 rounded-2xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:bg-white focus:ring-0 transition-all placeholder:text-gray-300">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 ml-1">Nomor HP Terdaftar</label>
                    <div class="relative">
                        <input type="text" name="phone" required placeholder="08xxxxxxxxxx" 
                            class="w-full px-6 py-4 bg-gray-50/50 border-2 border-gray-100 rounded-2xl text-sm font-bold text-gray-900 focus:border-indigo-600 focus:bg-white focus:ring-0 transition-all placeholder:text-gray-300">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-indigo-600/20 transform active:scale-[0.98] transition-all flex items-center justify-center space-x-3 group">
                        <span>MASUK SEKARANG</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>

            @if(session('success'))
            <div class="mt-6 p-4 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center space-x-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="text-emerald-600 text-[11px] font-bold">{{ session('success') }}</p>
            </div>
            @endif

            @if(session('error'))
            <div class="mt-6 p-4 bg-rose-50 rounded-xl border border-rose-100 flex items-center space-x-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-rose-600 text-[11px] font-bold">{{ session('error') }}</p>
            </div>
            @endif

            @if($errors->any())
            <div class="mt-6 p-4 bg-rose-50 rounded-xl border border-rose-100 flex items-center space-x-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-rose-600 text-[11px] font-bold">{{ $errors->first() }}</p>
            </div>
            @endif
        </div>

        <p class="text-center mt-8 text-xs font-bold text-gray-400">
            Butuh bantuan? <a href="#" class="text-indigo-600 hover:underline">Hubungi Layanan Pelanggan</a>
        </p>
    </div>
</body>
</html>
