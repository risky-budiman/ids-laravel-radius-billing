<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation - {{ get_setting('company_name', 'Radius Billing') }}</title>
    
    <!-- Dynamic Favicon -->
    @php 
        $appIcon = get_setting('app_icon'); 
        $companyLogo = get_setting('company_logo');
    @endphp
    @if($appIcon)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $appIcon) }}">
    @elseif($companyLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $companyLogo) }}">
    @endif
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Initialization -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; letter-spacing: -0.01em; }
        
        .sidebar-item-active {
            background: #4f46e5;
            color: white !important;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2);
        }

        /* High-End Typography */
        .prose-premium h1 { 
            font-size: 3rem; 
            font-weight: 900; 
            color: #0f172a; 
            margin-bottom: 3rem; 
            letter-spacing: -0.04em; 
            line-height: 1.1;
        }
        .prose-premium h2 { 
            font-size: 1.5rem; 
            font-weight: 800; 
            color: #1e293b; 
            margin-top: 4rem; 
            margin-bottom: 1.25rem; 
            letter-spacing: -0.02em;
        }
        .prose-premium p { 
            font-size: 1.125rem; 
            line-height: 1.9; 
            color: #475569; 
            margin-bottom: 2rem; 
        }
        .prose-premium ul { 
            list-style-type: none; 
            padding-left: 0.5rem; 
            margin-bottom: 2.5rem; 
        }
        .prose-premium li { 
            position: relative;
            padding-left: 1.75rem;
            margin-bottom: 0.75rem; 
            line-height: 1.7;
            color: #475569;
        }
        .prose-premium li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #6366f1;
            font-weight: 900;
        }
        .prose-premium strong { 
            color: #0f172a; 
            font-weight: 700; 
        }

        /* Dark Mode */
        .dark .prose-premium h1 { color: #f8fafc; }
        .dark .prose-premium h2 { color: #f1f5f9; }
        .dark .prose-premium p, .dark .prose-premium li { color: #94a3b8; }
        .dark .prose-premium strong { color: #f8fafc; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark ::-webkit-scrollbar-thumb { background: #1e293b; }
    </style>
</head>
<body class="bg-white dark:bg-slate-950 antialiased overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Ultra-Minimalist Sidebar -->
        <aside class="w-64 bg-slate-50/50 dark:bg-slate-950 border-r border-slate-100 dark:border-slate-900 hidden lg:flex flex-col z-30">
            <div class="flex-1 overflow-y-auto px-8 space-y-6" style="padding-top: 40px; padding-bottom: 60px;">
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-[0.25em] mb-6">Menu Panduan</h3>
                    <nav class="space-y-1">
                        @foreach($navigation as $nav)
                        <a href="{{ route('docs.index', $nav['slug']) }}" 
                           class="flex items-center px-4 py-2.5 rounded-xl text-[13px] transition-all duration-200 {{ $page === $nav['slug'] ? 'sidebar-item-active font-bold' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-300' }}">
                            {{ $nav['title'] }}
                        </a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <!-- Minimalist Exit -->
            <div class="p-8 border-t border-slate-100 dark:border-slate-900">
                <a href="{{ route('dashboard') }}" class="text-[10px] font-black text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 uppercase tracking-widest flex items-center transition-colors">
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Close Docs
                </a>
            </div>
        </aside>

        <!-- Main Content Container -->
        <main class="flex-1 overflow-y-auto bg-white dark:bg-slate-950">
            <div class="max-w-2xl px-8 md:px-12 animate-fade-in" style="padding-top: 40px; padding-bottom: 100px;">
                <!-- Content -->
                <article class="prose-premium">
                    {!! $html !!}
                </article>

                <!-- Simple Footer -->
                <div class="mt-32 pt-12 border-t border-slate-50 dark:border-slate-900">
                    <p class="text-[10px] font-bold text-slate-300 dark:text-slate-700 uppercase tracking-[0.3em]">
                        Documentation &bull; Updated {{ date('Y') }}
                    </p>
                </div>
            </div>
        </main>

    </div>

    @push('styles')
    <style>
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in {
            animation: fade-in 1s ease-out;
        }
    </style>
    @endpush
</body>
</html>
