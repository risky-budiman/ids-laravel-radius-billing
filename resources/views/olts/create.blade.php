<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Add New OLT') }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="glass bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <form action="{{ route('olts.store') }}" method="POST" class="p-8">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info Section -->
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">Basic Information</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">OLT Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="e.g. OLT STO JKT-01" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="olt_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">OLT Type</label>
                        <select name="olt_type" id="olt_type" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="ZTE_C320" {{ old('olt_type') == 'ZTE_C320' ? 'selected' : '' }}>ZTE ZXA10 C320</option>
                            <option value="ZTE_C300" {{ old('olt_type') == 'ZTE_C300' ? 'selected' : '' }}>ZTE ZXA10 C300</option>
                            <option value="HIOSO" {{ old('olt_type') == 'HIOSO' ? 'selected' : '' }}>Hioso (Generic)</option>
                            <option value="OTHER" {{ old('olt_type') == 'OTHER' ? 'selected' : '' }}>Other / Generic</option>
                        </select>
                        @error('olt_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="ip_address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Management IP Address</label>
                        <input type="text" name="ip_address" id="ip_address" value="{{ old('ip_address') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="10.10.10.2" required>
                        @error('ip_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Description / Location</label>
                        <input type="text" name="description" id="description" value="{{ old('description') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Optional notes">
                    </div>

                    <!-- SNMP Configuration Section -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-sm font-bold text-amber-600 dark:text-amber-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">SNMP Configuration</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_port" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Port</label>
                        <input type="number" name="snmp_port" id="snmp_port" value="{{ old('snmp_port', 161) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_version" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">SNMP Version</label>
                        <select name="snmp_version" id="snmp_version" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                            <option value="1" {{ old('snmp_version') == '1' ? 'selected' : '' }}>Version 1</option>
                            <option value="2" {{ old('snmp_version', '2') == '2' ? 'selected' : '' }}>Version 2c</option>
                        </select>
                        @error('snmp_version') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_read_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Read Community</label>
                        <input type="text" name="snmp_read_community" id="snmp_read_community" value="{{ old('snmp_read_community', 'public') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_read_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="snmp_write_community" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Write Community</label>
                        <input type="text" name="snmp_write_community" id="snmp_write_community" value="{{ old('snmp_write_community', 'private') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('snmp_write_community') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- CLI / Telnet Fallback Section -->
                    <div class="md:col-span-2 mt-4">
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">CLI / Telnet Configuration (Fallback)</h3>
                    </div>

                    <div class="space-y-2">
                        <label for="telnet_port" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Telnet/SSH Port</label>
                        <input type="number" name="telnet_port" id="telnet_port" value="{{ old('telnet_port', 23) }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" required>
                        @error('telnet_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Login Password</label>
                        <input type="password" name="password" id="password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all">
                    </div>

                    <div class="space-y-2">
                        <label for="enable_password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Enable Password</label>
                        <input type="password" name="enable_password" id="enable_password" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 transition-all" placeholder="Usually same as login password">
                    </div>

                    <div class="flex items-center space-x-3 mt-4">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Enable Device</label>
                    </div>
                </div>

                <div class="mt-10 flex justify-end space-x-4 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('olts.index') }}" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 transition-all">Cancel</a>
                    <button type="submit" class="px-10 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all">Save OLT Device</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
