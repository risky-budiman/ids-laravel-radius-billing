@props(['targetInputId' => null])

<div x-data="{ 
    scannerOpen: false,
    html5QrCode: null,
    startScanner() {
        this.scannerOpen = true;
        this.$nextTick(() => {
            this.html5QrCode = new Html5Qrcode('reader');
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            this.html5QrCode.start({ facingMode: 'environment' }, config, (decodedText) => {
                // Success callback
                const sn = decodedText.trim().toUpperCase();
                
                @if($targetInputId)
                    const target = document.getElementById('{{ $targetInputId }}');
                    if (target) {
                        target.value = sn;
                        target.dispatchEvent(new Event('input'));
                        target.dispatchEvent(new Event('change'));
                    }
                @endif
                
                // Always dispatch event for parent listeners
                this.$dispatch('scan-completed', sn);
                
                // Play success haptic
                if (window.navigator.vibrate) window.navigator.vibrate(100);
                
                this.stopScanner();
            }, (errorMessage) => {
                // parse error, ignore it.
            }).catch((err) => {
                console.error('Unable to start scanning', err);
                alert('Gagal mengakses kamera: ' + err);
                this.scannerOpen = false;
            });
        });
    },
    stopScanner() {
        if (this.html5QrCode) {
            this.html5QrCode.stop().then(() => {
                this.scannerOpen = false;
            }).catch(err => console.error('Failed to stop scanner', err));
        } else {
            this.scannerOpen = false;
        }
    }
}" @open-scanner.window="startScanner()">
    <!-- Scanner Button Trigger -->
    <button type="button" @click="startScanner()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-[10px] font-bold hover:bg-indigo-100 transition-colors border border-indigo-200 dark:border-indigo-800 shadow-sm">
        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        SCAN BARCODE / SN
    </button>

    <!-- Scanner Modal -->
    <div x-show="scannerOpen" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         style="display: none;">
        
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="stopScanner()"></div>
        
        <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-800 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    Scan Barcode / SN
                </h3>
                <button @click="stopScanner()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6">
                <div id="reader" class="w-full rounded-2xl overflow-hidden border-4 border-gray-100 dark:border-gray-800 shadow-inner bg-black aspect-square"></div>
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Arahkan kamera ke Barcode atau Serial Number perangkat.</p>
                    <button @click="stopScanner()" class="w-full py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 transition-all">
                        Batalkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    @endpush
@endonce
