<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Broadcast Pesan WhatsApp
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kirim pesan massal ke pelanggan berdasarkan wilayah atau status tertentu.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl dark:bg-emerald-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl dark:bg-red-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <form action="{{ route('whatsapp-broadcast.send') }}" method="POST" class="p-6" onsubmit="return confirm('Apakah Anda yakin ingin mengirim pesan massal (Broadcast) ini? Proses ini mungkin akan memakan waktu di latar belakang.');">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Kolom Kiri: Filter -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                                <span class="flex items-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                    Filter Penerima
                                </span>
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label for="region_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Region</label>
                                    <select id="region_code" name="region_code" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm">
                                        <option value="">-- Semua Region --</option>
                                        @foreach($regions as $r)
                                            <option value="{{ $r->code }}" data-id="{{ $r->id }}" {{ old('region_code') == $r->code ? 'selected' : '' }}>[{{ $r->code }}] {{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="sto_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">STO</label>
                                    <select id="sto_code" name="sto_code" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm disabled:opacity-50" disabled>
                                        <option value="">-- Semua STO --</option>
                                        @foreach($stos as $s)
                                            <option value="{{ $s->code }}" data-id="{{ $s->id }}" data-region-id="{{ $s->region_id }}" class="hidden" {{ old('sto_code') == $s->code ? 'selected' : '' }}>[{{ $s->code }}] {{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="stb_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">STB</label>
                                    <select id="stb_code" name="stb_code" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm disabled:opacity-50" disabled>
                                        <option value="">-- Semua STB --</option>
                                        @foreach($stbs as $t)
                                            <option value="{{ $t->code }}" data-sto-id="{{ $t->sto_id }}" class="hidden" {{ old('stb_code') == $t->code ? 'selected' : '' }}>[{{ $t->code }}] {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Pelanggan</label>
                                    <select id="status" name="status" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm">
                                        <option value="">-- Semua Status --</option>
                                        <option value="active">Active (Aktif)</option>
                                        <option value="suspended">Suspended (Isolir)</option>
                                        <option value="waiting_activation">Waiting Activation</option>
                                        <option value="unpaid">Menunggak (Unpaid Invoice)</option>
                                        <option value="paid">Lunas (Paid/No Unpaid Invoice)</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Pesan hanya akan dikirim ke pelanggan yang memiliki nomor WhatsApp.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Pesan -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                                <span class="flex items-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                    Pesan Broadcast
                                </span>
                            </h3>

                            <div>
                                <label for="template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Template</label>
                                <select id="template_id" name="template_id" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm" required>
                                    <option value="">-- Pilih Template --</option>
                                    @foreach($templates as $tmp)
                                        <option value="{{ $tmp->id }}" data-message="{{ $tmp->message }}">{{ $tmp->name }}</option>
                                    @endforeach
                                </select>
                                @error('template_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label for="custom_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Informasi Waktu / Tanggal (Opsional)</label>
                                <input type="datetime-local" id="custom_date" name="custom_date" value="{{ now()->addHours(24)->format('Y-m-d\TH:i') }}" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm">
                                <p class="mt-1 text-xs text-gray-500">Gunakan fitur kalender ini untuk mengisi variabel <code>{tanggal}</code>.</p>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pratinjau Pesan</label>
                                <div id="template-preview" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 h-48 overflow-y-auto border border-gray-200 dark:border-gray-700">
                                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Pilih template untuk melihat pratinjau pesan di sini...</p>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-emerald-600 border border-transparent rounded-xl font-semibold text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm shadow-emerald-500/30">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    Kirim Broadcast Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // CASCADING DROPDOWNS
        var regionSelect = document.getElementById('region_code');
        var stoSelect = document.getElementById('sto_code');
        var stbSelect = document.getElementById('stb_code');

        function filterStos() {
            var selectedRegionOption = regionSelect.options[regionSelect.selectedIndex];
            var regionId = selectedRegionOption ? selectedRegionOption.getAttribute('data-id') : null;

            var stoOptions = stoSelect.querySelectorAll('option:not([value=""])');
            var hasVisibleSto = false;

            stoOptions.forEach(function(opt) {
                if (!regionId || opt.getAttribute('data-region-id') == regionId) {
                    opt.classList.remove('hidden');
                    hasVisibleSto = true;
                } else {
                    opt.classList.add('hidden');
                }
            });

            stoSelect.disabled = !regionId;
            stoSelect.value = ''; // reset STO
            filterStbs(); // reset STB
        }

        function filterStbs() {
            var selectedStoOption = stoSelect.options[stoSelect.selectedIndex];
            var stoId = selectedStoOption && selectedStoOption.value ? selectedStoOption.getAttribute('data-id') : null;

            var stbOptions = stbSelect.querySelectorAll('option:not([value=""])');
            var hasVisibleStb = false;

            stbOptions.forEach(function(opt) {
                if (!stoId || opt.getAttribute('data-sto-id') == stoId) {
                    opt.classList.remove('hidden');
                    hasVisibleStb = true;
                } else {
                    opt.classList.add('hidden');
                }
            });

            stbSelect.disabled = !stoId;
            stbSelect.value = ''; // reset STB
        }

        regionSelect.addEventListener('change', filterStos);
        stoSelect.addEventListener('change', filterStbs);

        // PREVIEW TEMPLATE
        var templateSelect = document.getElementById('template_id');
        var customDateInput = document.getElementById('custom_date');
        
        function updatePreview() {
            var previewBox = document.getElementById('template-preview');
            var selectedOption = templateSelect.options[templateSelect.selectedIndex];
            
            if(selectedOption && selectedOption.value) {
                var message = selectedOption.getAttribute('data-message');
                var rawDate = customDateInput.value;
                var customDate = '[Waktu/Tanggal]';

                if (rawDate) {
                    // Convert 2026-04-28T14:00 to 28-04-2026 14:00
                    var d = new Date(rawDate);
                    var day = ("0" + d.getDate()).slice(-2);
                    var month = ("0" + (d.getMonth() + 1)).slice(-2);
                    var year = d.getFullYear();
                    var hours = ("0" + d.getHours()).slice(-2);
                    var minutes = ("0" + d.getMinutes()).slice(-2);
                    customDate = day + "-" + month + "-" + year + " " + hours + ":" + minutes;
                }
                
                // Replace variable {tanggal}
                message = message.replace(/{tanggal}/g, customDate);

                // Format basic WA markers for HTML preview
                message = message.replace(/\*(.*?)\*/g, '<b>$1</b>');
                message = message.replace(/_(.*?)_/g, '<i>$1</i>');
                message = message.replace(/~(.*?)~/g, '<del>$1</del>');
                // Newlines
                message = message.replace(/\n/g, '<br>');
                
                previewBox.innerHTML = '<p class="text-sm text-gray-700 dark:text-gray-300 font-mono">' + message + '</p>';
            } else {
                previewBox.innerHTML = '<p class="text-sm text-gray-400 dark:text-gray-500 italic">Pilih template untuk melihat pratinjau pesan di sini...</p>';
            }
        }

        templateSelect.addEventListener('change', updatePreview);
        customDateInput.addEventListener('input', updatePreview);
    </script>
    @endpush
</x-app-layout>
