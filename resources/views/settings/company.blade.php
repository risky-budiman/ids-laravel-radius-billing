<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Invoice & Portal</h2>
            <p class="page-subtitle">Format cetak invoice, catatan kaki, dan cara pelanggan masuk portal. Identitas perusahaan diatur di <a href="{{ route('settings.index') }}" class="text-primary-600 hover:underline dark:text-primary-400">Pengaturan Aplikasi</a>.</p>
        </div>
    </x-slot>

    <form action="{{ route('settings.company.update') }}" method="POST" class="max-w-3xl space-y-6">
        @csrf
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h3 class="card-title">Invoice</h3></div>
            <div class="card-body space-y-4">
                <div>
                    <label for="invoice_format" class="label">Format cetak bawaan</label>
                    <select id="invoice_format" name="invoice_format" class="input sm:max-w-xs">
                        <option value="A4" @selected(old('invoice_format', get_setting('invoice_format', 'A4')) === 'A4')>A4 (standar kantor)</option>
                        <option value="Thermal" @selected(old('invoice_format', get_setting('invoice_format')) === 'Thermal')>Struk thermal 80 mm</option>
                    </select>
                </div>
                <div>
                    <label for="invoice_footer_note" class="label">Catatan kaki invoice</label>
                    <textarea id="invoice_footer_note" name="invoice_footer_note" rows="3" class="input !h-auto" maxlength="1000"
                              placeholder="mis. Terima kasih. Pembayaran ke BCA 123456 a.n. PT …">{{ old('invoice_footer_note', get_setting('invoice_footer_note')) }}</textarea>
                    <p class="help-text">Dicetak di bagian bawah setiap invoice.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Portal pelanggan</h3></div>
            <div class="card-body">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="portal_login_otp" value="0">
                    <input type="checkbox" name="portal_login_otp" value="1" class="mt-1 rounded border-surface-300 text-primary-600 focus:ring-primary-500"
                           @checked(old('portal_login_otp', get_setting('portal_login_otp', '1')) == '1')>
                    <span>
                        <span class="block text-sm font-medium text-surface-900 dark:text-white">Masuk dengan kode OTP WhatsApp</span>
                        <span class="block text-xs text-surface-500 dark:text-surface-400">Setelah mengisi ID pelanggan & nomor HP, pelanggan menerima kode 6 digit ke WhatsApp terdaftar (portal web & aplikasi).
                            Matikan hanya bila gateway WhatsApp belum berfungsi — tanpa OTP, siapa pun yang tahu ID & nomor HP pelanggan bisa masuk.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button class="btn-primary">Simpan</button>
        </div>
    </form>
</x-app-layout>
