<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create New Ticket') }} - <span class="capitalize">{{ $type }}</span>
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="p-8">
                    <form action="{{ route('tickets.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="status" value="open">

                        <div class="space-y-6">
                            <!-- Customer Selection -->
                            <div>
                                <x-input-label for="customer_id" :value="__('Subscriber / Customer')" />
                                <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm transition-all duration-200">
                                    <option value="">-- No Customer (Internal Ticket) --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->username }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                            </div>

                            <!-- Subject -->
                            <div>
                                <x-input-label for="subject" :value="__('Subject')" />
                                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full !rounded-xl" :value="old('subject')" required placeholder="Brief description of the issue or request" />
                                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Priority -->
                                <div>
                                    <x-input-label for="priority" :value="__('Priority')" />
                                    <select id="priority" name="priority" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                                </div>

                                <!-- Assignee -->
                                <div>
                                    <x-input-label for="assigned_to" :value="__('Assign to Staff')" />
                                    <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Detailed Description')" />
                                <textarea id="description" name="description" rows="5" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm transition-all duration-200" required placeholder="Describe the activation details, error reports, or dismantle reasons...">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                                <a href="{{ route('tickets.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mr-4">
                                    {{ __('Cancel') }}
                                </a>
                                <x-primary-button class="!rounded-xl !py-3 !px-6 bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 dark:shadow-none">
                                    {{ __('Create Ticket') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
