<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Ticket') }}: {{ $ticket->ticket_number }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="p-8">
                    <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Segment Type -->
                                <div>
                                    <x-input-label for="type" :value="__('Ticket Segment')" />
                                    <select id="type" name="type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="aktivasi" {{ old('type', $ticket->type) == 'aktivasi' ? 'selected' : '' }}>Aktivasi</option>
                                        <option value="gangguan" {{ old('type', $ticket->type) == 'gangguan' ? 'selected' : '' }}>Gangguan</option>
                                        <option value="dismantle" {{ old('type', $ticket->type) == 'dismantle' ? 'selected' : '' }}>Dismantle</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>

                                <!-- Status -->
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="open" {{ old('status', $ticket->status) == 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ old('status', $ticket->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ old('status', $ticket->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ old('status', $ticket->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Customer Selection -->
                            <div>
                                <x-input-label for="customer_id" :value="__('Subscriber / Customer')" />
                                <select id="customer_id" name="customer_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                    <option value="">-- No Customer (Internal Ticket) --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id', $ticket->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->username }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                            </div>

                            <!-- Subject -->
                            <div>
                                <x-input-label for="subject" :value="__('Subject')" />
                                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full !rounded-xl" :value="old('subject', $ticket->subject)" required />
                                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Priority -->
                                <div>
                                    <x-input-label for="priority" :value="__('Priority')" />
                                    <select id="priority" name="priority" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="urgent" {{ old('priority', $ticket->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                                </div>

                                <!-- Assignee -->
                                <div>
                                    <x-input-label for="assigned_to" :value="__('Assign to Staff')" />
                                    <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to', $ticket->assigned_to) == $user->id ? 'selected' : '' }}>
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
                                <textarea id="description" name="description" rows="5" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm transition-all duration-200" required>{{ old('description', $ticket->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <!-- Resolution Notes -->
                            <div>
                                <x-input-label for="resolution_notes" :value="__('Resolution Notes (Optional)')" />
                                <textarea id="resolution_notes" name="resolution_notes" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm transition-all duration-200" placeholder="Describe how the ticket was resolved...">{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
                                <x-input-error :messages="$errors->get('resolution_notes')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end mt-8 border-t border-gray-100 dark:border-gray-700 pt-6">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mr-4">
                                    {{ __('Cancel') }}
                                </a>
                                <x-primary-button class="!rounded-xl !py-3 !px-6 bg-indigo-600 hover:bg-indigo-700">
                                    {{ __('Update Ticket') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
