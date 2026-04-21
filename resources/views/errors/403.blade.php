<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Unauthorized | {{ get_setting('company_name', 'Radius ISP') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            overflow: hidden;
        }

        .aurora {
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: pulse 15s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: translate(-20%, -20%) scale(1); }
            100% { transform: translate(10%, 10%) scale(1.2); }
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-gray-100">
    <div class="aurora bottom-0 left-0"></div>
    <div class="aurora top-0 right-0" style="background: radial-gradient(circle, rgba(168, 85, 247, 0.15) 0%, rgba(99, 102, 241, 0) 70%); animation-delay: -5s;"></div>

    <div class="glass max-w-lg w-full p-12 rounded-[2.5rem] relative overflow-hidden text-center mx-4 group">
        <!-- Decoration lines -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl -ml-16 -mb-16"></div>

        <div class="relative z-10">
            <!-- Icon -->
            <div class="mb-8 floating">
                <div class="w-24 h-24 bg-gradient-to-tr from-rose-500/20 to-orange-500/20 rounded-3xl mx-auto flex items-center justify-center border border-rose-500/30">
                    <svg class="w-12 h-12 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m0 0v3m0-3h3m-3 0H9m12-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <h1 class="text-6xl font-extrabold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-500">403</h1>
            <h2 class="text-2xl font-bold mb-4">Access Restricted</h2>
            <p class="text-gray-400 mb-10 leading-relaxed">
                Unauthorized: You do not have the required permissions to access this area.
            </p>

            <!-- Action -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-indigo-600/30 hover:scale-105 active:scale-95">
                    Return Dashboard
                </a>
                <button onclick="window.history.back()" class="px-8 py-4 bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold rounded-2xl transition-all border border-gray-700 hover:border-gray-600">
                    Go Back
                </button>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-800">
                <p class="text-xs text-gray-500 uppercase tracking-widest">{{ get_setting('company_name', 'ISP Management System') }} Security Protocol</p>
            </div>
        </div>
    </div>
</body>
</html>
