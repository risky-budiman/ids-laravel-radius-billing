<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ $template ? 'Edit Template: ' . $template->name : 'Buat Template Baru' }}
            </h2>
            <a href="{{ route('invoice-templates.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto py-4 px-4 sm:px-6 lg:px-8" x-data="templateEditor()" x-init="init()">
        <!-- Top Bar -->
        <form method="POST" action="{{ $template ? route('invoice-templates.update', $template) : route('invoice-templates.store') }}" id="templateForm">
            @csrf
            @if($template) @method('PUT') @endif

            <div class="flex flex-wrap items-center gap-4 mb-4 p-4 glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" placeholder="Nama Template (misal: Invoice Standar A4)" required
                        class="w-full border-none bg-gray-50 dark:bg-gray-900 rounded-xl py-2.5 px-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <select name="format" class="border-none bg-gray-50 dark:bg-gray-900 rounded-xl py-2.5 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500">
                        <option value="A4" {{ old('format', $template->format ?? 'A4') == 'A4' ? 'selected' : '' }}>A4 / Letter</option>
                        <option value="Thermal" {{ old('format', $template->format ?? '') == 'Thermal' ? 'selected' : '' }}>Thermal 80mm</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $template->is_default ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="font-medium">Jadikan Default</span>
                </label>
                <button type="button" @click="refreshPreview()" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Preview
                </button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all active:scale-95 shadow-lg shadow-indigo-500/30 flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Template
                </button>
            </div>

            <!-- Editor + Preview Split -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="min-height: 75vh;">
                <!-- LEFT: Code Editor -->
                <div class="flex flex-col gap-4">
                    <!-- Variable Reference -->
                    <div class="glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4" x-data="{ varOpen: false }">
                        <button type="button" @click="varOpen = !varOpen" class="w-full flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-300">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                Variabel yang Tersedia (Klik untuk menyisipkan)
                            </span>
                            <svg class="w-4 h-4 transition-transform" :class="varOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="varOpen" x-transition class="mt-4 space-y-4" style="display:none;">
                            @foreach($variables as $group => $vars)
                            <div>
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ $group }}</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($vars as $var => $desc)
                                    <button type="button" @click="insertVariable('{{ $var }}')" title="{{ $desc }}"
                                        class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors cursor-pointer">
                                        {{ $var }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- HTML Editor -->
                    <div class="flex-1 glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">HTML Content</span>
                            <span class="text-[10px] text-gray-400">Tulis kode HTML template invoice Anda</span>
                        </div>
                        <textarea name="html_content" id="htmlEditor" x-ref="htmlEditor"
                            class="flex-1 w-full border-none bg-gray-900 text-emerald-400 font-mono text-sm p-4 focus:ring-0 resize-none"
                            spellcheck="false" style="min-height: 300px; tab-size: 2;"
                            @keydown.tab.prevent="insertTab($event)">{{ old('html_content', $template->html_content ?? $defaultHtml) }}</textarea>
                    </div>

                    <!-- CSS Editor -->
                    <div class="glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                        <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">CSS (Opsional)</span>
                            <span class="text-[10px] text-gray-400">Style tambahan untuk template</span>
                        </div>
                        <textarea name="css_content" id="cssEditor" x-ref="cssEditor"
                            class="w-full border-none bg-gray-900 text-sky-400 font-mono text-sm p-4 focus:ring-0 resize-none"
                            spellcheck="false" style="min-height: 120px; tab-size: 2;"
                            @keydown.tab.prevent="insertTab($event)">{{ old('css_content', $template->css_content ?? '') }}</textarea>
                    </div>
                </div>

                <!-- RIGHT: Live Preview -->
                <div class="glass bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
                    <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Live Preview</span>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" :class="previewLoading ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></span>
                            <span class="text-[10px] text-gray-400" x-text="previewLoading ? 'Loading...' : 'Ready'"></span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-auto bg-gray-100 dark:bg-gray-950 p-6">
                        <div class="bg-white shadow-lg mx-auto" style="max-width: 210mm; min-height: 297mm; padding: 20mm;">
                            <div id="previewContent" x-html="previewHtml">
                                <div class="text-center text-gray-400 py-20">
                                    <p class="text-sm">Klik tombol <strong>Preview</strong> atau tekan <kbd class="px-1.5 py-0.5 bg-gray-200 rounded text-xs">Ctrl+Enter</kbd> untuk melihat hasil.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function templateEditor() {
            return {
                previewHtml: '',
                previewLoading: false,

                init() {
                    // Auto-preview on Ctrl+Enter
                    document.addEventListener('keydown', (e) => {
                        if (e.ctrlKey && e.key === 'Enter') {
                            e.preventDefault();
                            this.refreshPreview();
                        }
                    });

                    // Auto-preview on first load if there's content
                    const htmlEditor = document.getElementById('htmlEditor');
                    if (htmlEditor && htmlEditor.value.trim().length > 10) {
                        setTimeout(() => this.refreshPreview(), 500);
                    }
                },

                insertVariable(variable) {
                    const editor = document.getElementById('htmlEditor');
                    const start = editor.selectionStart;
                    const end = editor.selectionEnd;
                    const text = editor.value;
                    editor.value = text.substring(0, start) + variable + text.substring(end);
                    editor.selectionStart = editor.selectionEnd = start + variable.length;
                    editor.focus();
                },

                insertTab(event) {
                    const editor = event.target;
                    const start = editor.selectionStart;
                    const end = editor.selectionEnd;
                    editor.value = editor.value.substring(0, start) + '  ' + editor.value.substring(end);
                    editor.selectionStart = editor.selectionEnd = start + 2;
                },

                async refreshPreview() {
                    this.previewLoading = true;
                    const htmlContent = document.getElementById('htmlEditor').value;
                    const cssContent = document.getElementById('cssEditor').value;

                    try {
                        const response = await fetch('{{ route("invoice-templates.preview") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'text/html',
                            },
                            body: JSON.stringify({
                                html_content: htmlContent,
                                css_content: cssContent,
                            }),
                        });
                        this.previewHtml = await response.text();
                    } catch (err) {
                        this.previewHtml = '<div style="color:red;padding:20px;">Error loading preview: ' + err.message + '</div>';
                    }
                    this.previewLoading = false;
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
