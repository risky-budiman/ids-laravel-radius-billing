<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-4">
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.tickets.index') }}" class="btn-ghost btn-icon -ml-2" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-surface-900 dark:text-white">Laporkan Gangguan</h1>
                <p class="text-sm text-surface-500 dark:text-surface-400">Ceritakan kendalanya agar teknisi kami bisa segera membantu.</p>
            </div>
        </div>

        <form action="{{ route('customer.tickets.store') }}" method="POST" enctype="multipart/form-data" class="card p-5 sm:p-6 space-y-5"
              x-data="{ sending: false }" @submit="sending = true">
            @csrf

            <div>
                <label for="subject" class="label">Kendala</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required autofocus list="subject-suggestions"
                       class="input @error('subject') input-error @enderror" placeholder="Contoh: Internet mati total">
                <datalist id="subject-suggestions">
                    <option value="Internet mati total">
                    <option value="Lampu LOS merah di modem">
                    <option value="Koneksi lambat">
                    <option value="WiFi tidak muncul">
                    <option value="Sering putus-putus">
                </datalist>
                <x-input-error class="mt-1.5" :messages="$errors->get('subject')" />
            </div>

            <div>
                <label for="description" class="label">Penjelasan</label>
                <textarea id="description" name="description" rows="5" required
                          class="input @error('description') input-error @enderror"
                          placeholder="Sejak kapan bermasalah, lampu apa saja yang menyala di modem, perangkat apa yang terdampak…">{{ old('description') }}</textarea>
                <x-input-error class="mt-1.5" :messages="$errors->get('description')" />
            </div>

            <div>
                <label for="attachment" class="label">Foto (opsional)</label>
                <input type="file" id="attachment" name="attachment" accept="image/*"
                       class="block w-full text-sm text-surface-600 dark:text-surface-300 file:mr-3 file:h-9 file:px-3.5 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-surface-100 file:text-surface-700 hover:file:bg-surface-200 dark:file:bg-surface-800 dark:file:text-surface-200">
                <p class="help-text">JPG atau PNG, maksimal 5 MB. Foto lampu modem sangat membantu teknisi.</p>
                <x-input-error class="mt-1.5" :messages="$errors->get('attachment')" />
            </div>

            <div class="flex items-start gap-2.5 rounded-lg bg-surface-50 dark:bg-surface-800/50 px-3.5 py-3">
                <svg class="w-5 h-5 text-surface-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-surface-600 dark:text-surface-300">Biarkan modem tetap menyala agar sistem kami bisa mengecek koneksi Anda dari jarak jauh.</p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <a href="{{ route('customer.tickets.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary" :disabled="sending">
                    <span x-text="sending ? 'Mengirim…' : 'Kirim laporan'">Kirim laporan</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
