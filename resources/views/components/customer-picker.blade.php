{{--
    Pencarian pelanggan untuk formulir (pengganti <select> berisi SELURUH pelanggan, yang lambat bila ribuan).
    Pemakaian: <x-customer-picker name="customer_id" :value="$id" :customer="$customerModel" empty-label="Tanpa pelanggan" />
--}}
@props(['name' => 'customer_id', 'value' => null, 'customer' => null, 'emptyLabel' => null, 'required' => false, 'placeholder' => 'Ketik nama, ID pelanggan, username, atau no. HP…'])
@php
    $selected = $customer ?? ($value ? \App\Models\Customer::find($value) : null);
    $initial = $selected ? ['id' => $selected->id, 'label' => $selected->name, 'detail' => trim($selected->customer_code . ' · ' . $selected->username, ' ·')] : null;
@endphp
<div x-data="customerPicker(@js($initial), @js(route('api.customers.lookup')))" class="relative" @click.outside="open = false">
    <input type="hidden" name="{{ $name }}" :value="selected ? selected.id : ''" @if($required) required @endif>
    <template x-if="selected">
        <div class="mt-1 flex items-center justify-between gap-2 rounded-lg border border-surface-300 bg-white px-3 py-2 text-sm dark:border-surface-700 dark:bg-surface-900">
            <span><span class="font-medium text-surface-900 dark:text-white" x-text="selected.label"></span>
                <span class="ml-1 font-mono text-xs text-surface-500" x-text="selected.detail"></span></span>
            <button type="button" class="text-xs text-surface-500 hover:text-rose-600" @click="selected = null; $nextTick(() => $refs.q.focus())">Ganti</button>
        </div>
    </template>
    <div x-show="!selected">
        <input type="text" x-ref="q" x-model="q" @input.debounce.300ms="search()" @focus="open = results.length > 0" @keydown.escape="open = false"
               class="mt-1 input" placeholder="{{ $placeholder }}" autocomplete="off" @if($required) :required="!selected" @endif>
        @if($emptyLabel)
            <p class="help-text">Kosongkan untuk: {{ $emptyLabel }}</p>
        @endif
    </div>
    <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-surface-200 bg-white shadow-lg dark:border-surface-700 dark:bg-surface-900">
        <template x-for="r in results" :key="r.id">
            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-surface-50 dark:hover:bg-surface-800" @click="selected = r; open = false; q = ''">
                <span class="font-medium text-surface-900 dark:text-white" x-text="r.label"></span>
                <span class="block font-mono text-xs text-surface-500" x-text="r.detail"></span>
            </button>
        </template>
        <p x-show="!loading && results.length === 0 && q.length >= 2" class="px-3 py-2 text-sm text-surface-500">Tidak ditemukan.</p>
        <p x-show="loading" class="px-3 py-2 text-sm text-surface-500">Mencari…</p>
    </div>
</div>
@once
    <script>
        function customerPicker(initial, url) {
            return {
                q: '', results: [], open: false, loading: false, selected: initial,
                async search() {
                    if (this.q.trim().length < 2) { this.results = []; this.open = false; return; }
                    this.loading = true; this.open = true;
                    try {
                        const res = await fetch(url + '?q=' + encodeURIComponent(this.q.trim()), { headers: { Accept: 'application/json' } });
                        this.results = res.ok ? await res.json() : [];
                    } catch (e) { this.results = []; }
                    this.loading = false;
                },
            };
        }
    </script>
@endonce
