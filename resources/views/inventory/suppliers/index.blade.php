<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Inventory: Suppliers') }}
            </h2>
            <button onclick="document.getElementById('addSupplierModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-600/20">
                + Add Supplier
            </button>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-100/80 border border-emerald-200 text-emerald-700 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">#</th>
                        <th class="px-6 py-4 font-bold">Supplier Name</th>
                        <th class="px-6 py-4 font-bold">Contact Person</th>
                        <th class="px-6 py-4 font-bold">Phone / Email</th>
                        <th class="px-6 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30 transition-colors group">
                            <td class="px-6 py-4 text-gray-400 dark:text-gray-500 text-sm font-medium">
                                {{ $suppliers->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $supplier->name }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5 truncate max-w-xs">{{ $supplier->address }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $supplier->contact_person ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">
                                <div class="text-gray-700 dark:text-gray-300">{{ $supplier->phone ?? '-' }}</div>
                                <div class="text-gray-400 font-normal mt-0.5">{{ $supplier->email ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($supplier->is_active)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-lg uppercase">Active</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-400 text-[10px] font-bold rounded-lg uppercase">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <button class="text-indigo-600 hover:text-indigo-800 text-xs font-bold">Edit</button>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-bold">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Add Supplier -->
    <div id="addSupplierModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="glass bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg border border-white/20 overflow-hidden transform transition-all">
            <div class="bg-indigo-600 px-6 py-4 text-white">
                <h3 class="font-bold text-lg">Add New Supplier</h3>
            </div>
            <form action="{{ route('suppliers.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label value="Supplier Name" />
                        <x-text-input name="name" required class="mt-1 w-full" placeholder="e.g. PT. Fiber Jaya" />
                    </div>
                    <div>
                        <x-input-label value="Contact Person" />
                        <x-text-input name="contact_person" class="mt-1 w-full" placeholder="e.g. Mr. John Doe" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Phone" />
                            <x-text-input name="phone" class="mt-1 w-full" placeholder="081..." />
                        </div>
                        <div>
                            <x-input-label value="Email" />
                            <x-text-input name="email" type="email" class="mt-1 w-full" placeholder="sales@fiber.com" />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Address" />
                        <textarea name="address" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm" rows="3"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
