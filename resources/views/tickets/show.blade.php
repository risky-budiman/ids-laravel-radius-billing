<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Ticket Details') }}: {{ $ticket->ticket_number }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('tickets.edit', $ticket) }}" class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit Ticket
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Ticket Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $ticket->subject }}</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @php
                                            $typeColors = [
                                                'aktivasi' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                                'gangguan' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                                'dismantle' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                            ];
                                            $statusColors = [
                                                'open' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                'resolved' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                'closed' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                                'canceled' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                            ];
                                        @endphp
                                        <span class="px-3 py-1 text-xs font-bold rounded-full {{ $typeColors[$ticket->type] ?? 'bg-gray-100' }}">
                                            {{ strtoupper($ticket->type) }}
                                        </span>
                                        <span class="px-3 py-1 text-xs font-bold rounded-full {{ $statusColors[$ticket->status] ?? 'bg-gray-100' }}">
                                            {{ str_replace('_', ' ', strtoupper($ticket->status)) }}
                                        </span>
                                        @if($ticket->isOverdue())
                                            <span class="px-3 py-1 text-xs font-black rounded-full bg-rose-600 text-white animate-pulse shadow-lg shadow-rose-500/50">
                                                OVERDUE (SLA > 24H)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Created At</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
                                </div>
                            </div>

                            <div class="prose dark:prose-invert max-w-none mb-8 bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Description</h4>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->description }}</p>
                            </div>

                            @if($ticket->resolution_notes)
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-900/50 mb-8">
                                    <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-4">Resolution Notes</h4>
                                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->resolution_notes }}</p>
                                </div>
                            @endif

                            <!-- Ticket Activity / Replies -->
                            <div class="mt-12">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    Activity Log & Replies
                                </h4>
                                
                                <div class="space-y-6 mb-8">
                                    @forelse($ticket->replies as $reply)
                                        <div class="flex space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                                    {{ substr($reply->user->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <div class="bg-white dark:bg-gray-800/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                                                    <div class="flex justify-between items-center mb-3">
                                                        <span class="font-bold text-gray-900 dark:text-white">{{ $reply->user->name }}</span>
                                                        <span class="text-[10px] text-gray-400 uppercase">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ $reply->message }}</p>
                                                    
                                                    @if($reply->attachment)
                                                        <div class="mt-4 pt-4 border-t border-gray-50 dark:border-gray-700/50">
                                                            <a href="{{ asset('storage/' . $reply->attachment) }}" target="_blank" class="inline-flex items-center text-xs text-indigo-600 hover:underline">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                                View Attachment
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-center py-8 text-gray-400 italic text-sm">No replies yet. Be the first to reply.</p>
                                    @endforelse
                                </div>

                                <!-- Reply Form -->
                                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl">
                                    <form action="{{ route('tickets.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="space-y-4">
                                            <textarea name="message" rows="4" class="block w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-2xl text-sm transition-all" placeholder="Add a comment or update..." required></textarea>
                                            
                                            <div class="flex items-center justify-between">
                                                <div class="relative">
                                                    <input type="file" name="attachment" id="attachment" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                                    <label for="attachment" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                        Attach File
                                                    </label>
                                                </div>
                                                <x-primary-button type="submit" class="rounded-xl">
                                                    Post Reply
                                                </x-primary-button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Sidebar / Meta Info -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Information</h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Subscriber</p>
                                    @if($ticket->customer)
                                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $ticket->customer->name }}</p>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <p class="text-sm text-gray-500 italic">{{ $ticket->customer->username }}</p>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 uppercase tracking-tighter">
                                                {{ str_replace('_', ' ', $ticket->customer->status) }}
                                            </span>
                                        </div>
                                        <a href="{{ route('customers.show', $ticket->customer) }}" class="mt-2 text-xs text-indigo-600 hover:underline inline-block">View Customer Profile</a>
                                    @else
                                        <p class="mt-1 font-semibold text-gray-400">Internal / Not Specific</p>
                                    @endif
                                </div>

                                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Priority</p>
                                    <div class="mt-2 flex items-center">
                                        @php
                                            $priorityColors = [
                                                'low' => 'bg-gray-400',
                                                'medium' => 'bg-blue-500',
                                                'high' => 'bg-orange-500',
                                                'urgent' => 'bg-rose-600',
                                            ];
                                        @endphp
                                        <div class="px-3 py-1 rounded-full {{ $priorityColors[$ticket->priority] }} text-white text-[10px] font-black uppercase shadow-sm">
                                            {{ $ticket->priority }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Assigned Staff</p>
                                    <div class="mt-2 flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs mr-3">
                                            {{ substr($ticket->assignee->name ?? '?', 0, 1) }}
                                        </div>
                                        <p class="font-medium text-gray-900 dark:text-white leading-tight">
                                            {{ $ticket->assignee->name ?? 'Unassigned' }}
                                        </p>
                                    </div>
                                    @if(!$ticket->assigned_to && (auth()->user()->isTeknisi() || auth()->user()->isAdmin() || auth()->user()->isAdministrator()))
                                        <form action="{{ route('tickets.claim', $ticket) }}" method="POST" class="mt-4">
                                            @csrf
                                            <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Klaim Tiket Ini
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-900 text-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-800 p-6">
                        <h4 class="text-lg font-bold mb-4">Quick Update</h4>
                        <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <!-- Keep old values for unedited fields -->
                            <input type="hidden" name="type" value="{{ $ticket->type }}">
                            <input type="hidden" name="subject" value="{{ $ticket->subject }}">
                            <input type="hidden" name="description" value="{{ $ticket->description }}">
                            <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                            <input type="hidden" name="assigned_to" value="{{ $ticket->assigned_to }}">
                            <input type="hidden" name="customer_id" value="{{ $ticket->customer_id }}">

                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs text-gray-400 uppercase font-bold tracking-wider">Change Status</label>
                                    <select name="status" class="mt-2 block w-full bg-gray-800 border-gray-700 text-gray-300 rounded-xl focus:border-indigo-500 focus:ring-indigo-500 transition-all duration-200">
                                        <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                                        <option value="canceled" {{ $ticket->status == 'canceled' ? 'selected' : '' }}>Canceled</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs text-gray-400 uppercase font-bold tracking-wider">Add/Edit Resolution Notes</label>
                                    <textarea name="resolution_notes" rows="3" class="mt-2 block w-full bg-gray-800 border-gray-700 text-gray-300 rounded-xl focus:border-indigo-500 focus:ring-indigo-500 transition-all duration-200" placeholder="Type resolution details here...">{{ $ticket->resolution_notes }}</textarea>
                                </div>

                                <button type="submit" class="w-full py-3 px-4 bg-indigo-500 hover:bg-indigo-400 text-white rounded-xl font-bold transition-colors">
                                    Update Ticket Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
