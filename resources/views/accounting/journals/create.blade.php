<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('accounting.journals.index') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-indigo-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                Input Jurnal Baru
            </h2>
        </div>
    </x-slot>

    <div class="py-12" x-data="journalForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('accounting.journals.store') }}" method="POST" @submit.prevent="submitForm">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sidebar: Header Info -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Informasi Utama</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Transaksi</label>
                                    <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi / Keterangan</label>
                                    <textarea name="description" rows="3" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Setoran modal awal atau Biaya maintenance bulanan"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Status Balance Card -->
                        <div :class="isBalanced ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-800' : 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-800'" class="rounded-3xl p-6 border transition-colors">
                            <div class="flex items-center justify-between mb-4">
                                <h3 :class="isBalanced ? 'text-emerald-900 dark:text-emerald-400' : 'text-rose-900 dark:text-rose-400'" class="font-bold">Status Balance</h3>
                                <template x-if="isBalanced">
                                    <svg class="w-6 h-6 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </template>
                                <template x-if="!isBalanced">
                                    <svg class="w-6 h-6 text-rose-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                </template>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Debit:</span>
                                    <span class="font-mono font-bold" x-text="formatCurrency(totalDebit)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Kredit:</span>
                                    <span class="font-mono font-bold" x-text="formatCurrency(totalCredit)"></span>
                                </div>
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between font-black">
                                    <span>Selisih:</span>
                                    <span :class="difference === 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="formatCurrency(difference)"></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" :disabled="!isBalanced || items.length < 2" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-black rounded-3xl shadow-xl shadow-indigo-500/20 transition-all transform active:scale-95">
                            POSTING JURNAL
                        </button>
                    </div>

                    <!-- Main Content: Journal Lines -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Akun</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right w-40">Debit</th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right w-40">Kredit</th>
                                        <th class="px-6 py-4 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="border-b border-gray-50 dark:border-gray-700/50">
                                            <td class="p-4">
                                                <select :name="'items['+index+'][account_id]'" x-model="item.account_id" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-sm focus:ring-indigo-500">
                                                    <option value="">Pilih Akun...</option>
                                                    @foreach($accounts as $account)
                                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="p-4">
                                                <input type="number" step="0.01" :name="'items['+index+'][debit]'" x-model.number="item.debit" @input="updateTotals" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-right font-mono text-sm focus:ring-indigo-500" placeholder="0,00">
                                            </td>
                                            <td class="p-4">
                                                <input type="number" step="0.01" :name="'items['+index+'][credit]'" x-model.number="item.credit" @input="updateTotals" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-right font-mono text-sm focus:ring-rose-500" placeholder="0,00">
                                            </td>
                                            <td class="p-4">
                                                <button type="button" @click="removeItem(index)" class="text-gray-400 hover:text-rose-500 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div class="p-6 bg-gray-50/50 dark:bg-gray-900/50">
                                <button type="button" @click="addItem" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Tambah Baris Akun
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function journalForm() {
            return {
                items: [
                    { account_id: '', debit: 0, credit: 0 },
                    { account_id: '', debit: 0, credit: 0 }
                ],
                totalDebit: 0,
                totalCredit: 0,
                difference: 0,
                isBalanced: true,

                addItem() {
                    this.items.push({ account_id: '', debit: 0, credit: 0 });
                },

                removeItem(index) {
                    if (this.items.length > 2) {
                        this.items.splice(index, 1);
                        this.updateTotals();
                    }
                },

                updateTotals() {
                    this.totalDebit = this.items.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0);
                    this.totalCredit = this.items.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0);
                    this.difference = Math.abs(this.totalDebit - this.totalCredit);
                    this.isBalanced = this.difference < 0.01 && (this.totalDebit > 0);
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
                },

                submitForm(e) {
                    if (!this.isBalanced) {
                        alert('Jurnal belum seimbang (Balance)! Total Debit harus sama dengan Kredit.');
                        return;
                    }
                    e.target.submit();
                }
            }
        }
    </script>
</x-app-layout>
