@php
    $v = fn ($k, $d = '') => old($k, $settings[$k]->value ?? $d);
    $on = fn ($k) => old($k, $settings[$k]->value ?? '0') == '1';
    $logo = $settings['company_logo']->value ?? null;
    $icon = $settings['app_icon']->value ?? null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Pengaturan Aplikasi</h2>
            <p class="page-subtitle">Identitas perusahaan, modul tambahan, dan grup WhatsApp notifikasi tiket.</p>
        </div>
    </x-slot>

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Identitas --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Identitas perusahaan</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">Tampil di invoice, laporan, Berita Acara, halaman login & pesan WhatsApp.</p>
            </div>
            <div class="card-body grid gap-6 md:grid-cols-[180px_minmax(0,1fr)]">
                <div class="space-y-5">
                    <div>
                        <span class="label">Logo</span>
                        <label class="flex h-28 cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-surface-200 bg-surface-50 hover:border-primary-300 dark:border-surface-700 dark:bg-surface-900">
                            <img id="logo_preview" src="{{ $logo ? asset('storage/' . $logo) : '' }}" alt="" class="{{ $logo ? '' : 'hidden' }} h-full w-full object-contain p-3">
                            <span class="{{ $logo ? 'hidden' : '' }} text-xs text-surface-400" id="logo_empty">Pilih gambar</span>
                            <input type="file" name="company_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden"
                                   onchange="if(this.files[0]){const i=document.getElementById('logo_preview');i.src=URL.createObjectURL(this.files[0]);i.classList.remove('hidden');document.getElementById('logo_empty').classList.add('hidden');}">
                        </label>
                        <p class="help-text">PNG/SVG transparan, maks. 2 MB.</p>
                    </div>
                    <div>
                        <span class="label">Ikon aplikasi (favicon)</span>
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-surface-200 p-2 hover:border-primary-300 dark:border-surface-700">
                            <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded bg-surface-50 dark:bg-surface-900">
                                <img id="icon_preview" src="{{ $icon ? asset('storage/' . $icon) : '' }}" alt="" class="{{ $icon ? '' : 'hidden' }} h-full w-full object-contain">
                            </span>
                            <span class="text-xs text-surface-500">Unggah ikon<br>PNG/ICO persegi</span>
                            <input type="file" name="app_icon" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/svg+xml" class="hidden"
                                   onchange="if(this.files[0]){const i=document.getElementById('icon_preview');i.src=URL.createObjectURL(this.files[0]);i.classList.remove('hidden');}">
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="company_name" class="label">Nama perusahaan</label>
                        <input id="company_name" name="company_name" class="input" value="{{ $v('company_name') }}" required maxlength="150">
                    </div>
                    <div>
                        <label for="company_tax_id" class="label">NPWP</label>
                        <input id="company_tax_id" name="company_tax_id" class="input font-mono" value="{{ $v('company_tax_id') }}" placeholder="00.000.000.0-000.000" maxlength="30">
                    </div>
                    <div>
                        <label for="company_email" class="label">Email</label>
                        <input id="company_email" name="company_email" type="email" class="input" value="{{ $v('company_email') }}" placeholder="billing@perusahaan.id">
                    </div>
                    <div>
                        <label for="company_phone" class="label">Nomor kontak / WhatsApp</label>
                        <input id="company_phone" name="company_phone" class="input" value="{{ $v('company_phone') }}" placeholder="08…" maxlength="20">
                        <p class="help-text">Dipakai tombol "Hubungi kami" di portal & halaman isolir.</p>
                    </div>
                    <div>
                        <label for="company_website" class="label">Website</label>
                        <input id="company_website" name="company_website" type="url" class="input" value="{{ $v('company_website') }}" placeholder="https://…">
                    </div>
                    <div>
                        <label for="company_domain" class="label">Akhiran username PPPoE</label>
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-surface-300 bg-surface-50 px-3 text-sm text-surface-500 dark:border-surface-700 dark:bg-surface-800">@</span>
                            <input id="company_domain" name="company_domain" class="input !rounded-l-none font-mono" value="{{ $v('company_domain') }}" placeholder="perusahaan" maxlength="60">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company_address" class="label">Alamat kantor</label>
                        <textarea id="company_address" name="company_address" rows="2" class="input !h-auto" maxlength="500">{{ $v('company_address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modul --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Modul tambahan</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">Menu & perhitungan modul hanya muncul bila diaktifkan.</p>
            </div>
            <div class="card-body divide-y divide-surface-100 dark:divide-surface-800">
                @foreach([
                    ['enable_partner_module', 'Mitra (B2B / reseller)', 'Pelanggan bisa dihubungkan ke mitra yang mendapat komisi dari setiap pembayaran.', 'default_commission_rate', 'commission_type', 'Komisi bawaan'],
                    ['enable_sales_commission_module', 'Insentif sales', 'Sales internal mendapat insentif dari pelanggan yang didaftarkannya.', 'default_sales_commission_rate', 'default_sales_commission_type', 'Insentif bawaan'],
                    ['enable_fup_module', 'FUP & kuota', 'Mencatat pemakaian kuota dan menurunkan kecepatan otomatis setelah batas paket.', null, null, null],
                ] as [$key, $title, $desc, $rate, $type, $rateLabel])
                    <div class="grid gap-4 py-4 first:pt-0 last:pb-0 sm:grid-cols-[minmax(0,1fr)_auto]" x-data="{ on: {{ $on($key) ? 'true' : 'false' }} }">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="{{ $key }}" value="1" x-model="on" class="mt-1 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                            <span>
                                <span class="block text-sm font-medium text-surface-900 dark:text-white">{{ $title }}</span>
                                <span class="block text-xs text-surface-500 dark:text-surface-400">{{ $desc }}</span>
                            </span>
                        </label>
                        @if($rate)
                            <div class="flex items-end gap-2" :class="on ? '' : 'opacity-50'">
                                <div>
                                    <label for="{{ $rate }}" class="label !text-xs">{{ $rateLabel }}</label>
                                    <input id="{{ $rate }}" name="{{ $rate }}" type="number" step="0.01" min="0" class="input !w-28 text-right tabular-nums" value="{{ $v($rate, '0') }}">
                                </div>
                                <select name="{{ $type }}" class="input !w-36" aria-label="Jenis {{ strtolower($rateLabel) }}">
                                    <option value="percentage" @selected($v($type, 'percentage') === 'percentage')>Persen (%)</option>
                                    <option value="fixed" @selected($v($type, 'percentage') === 'fixed')>Nominal (Rp)</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Grup WhatsApp tiket --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notifikasi tiket ke grup WhatsApp</h3>
                <p class="text-xs text-surface-500 dark:text-surface-400">ID grup berformat <span class="font-mono">120363xxxxxxxx@g.us</span> — lihat di dasbor penyedia (Fonnte/Wablas) atau kirim <span class="font-mono">/groups</span> ke nomor API.</p>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <label for="whatsapp_group_id" class="label">Grup bawaan</label>
                    <input id="whatsapp_group_id" name="whatsapp_group_id" class="input font-mono" value="{{ $v('whatsapp_group_id') }}" placeholder="120363…@g.us" maxlength="80">
                    <p class="help-text">Dipakai untuk jenis tiket yang grupnya di bawah dikosongkan.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach(['gangguan' => 'Gangguan', 'aktivasi' => 'Aktivasi', 'dismantle' => 'Dismantle', 'relokasi' => 'Relokasi', 'maintenance' => 'Maintenance'] as $type => $label)
                        <div>
                            <label for="whatsapp_group_id_{{ $type }}" class="label">Tiket {{ strtolower($label) }}</label>
                            <input id="whatsapp_group_id_{{ $type }}" name="whatsapp_group_id_{{ $type }}" class="input font-mono text-xs" value="{{ $v('whatsapp_group_id_' . $type) }}" placeholder="(grup bawaan)" maxlength="80">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-surface-500 dark:text-surface-400">Format invoice, catatan kaki & login OTP portal: <a href="{{ route('settings.company') }}" class="text-primary-600 hover:underline dark:text-primary-400">Invoice & Portal</a>.</p>
            <button class="btn-primary">Simpan pengaturan</button>
        </div>
    </form>
</x-app-layout>
