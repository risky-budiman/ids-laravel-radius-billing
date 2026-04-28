<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('noc.signals') }}" class="p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-indigo-500 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white tracking-tight">
                    Signal History: {{ $customer->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analisis tren redaman kabel dalam 30 hari terakhir.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Current Status</p>
                    <div class="mt-2 flex items-center gap-2">
                        @php
                            $lastLog = $logs->last();
                            $lastPower = $lastLog ? $lastLog->rx_power : null;
                        @endphp
                        <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $lastPower ?? 'N/A' }} dBm</span>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Average (30 Days)</p>
                    <div class="mt-2">
                        <span class="text-2xl font-black text-indigo-600">{{ round($logs->avg('rx_power'), 2) }} dBm</span>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">OLT Source</p>
                    <div class="mt-2">
                        <span class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ $customer->olt->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <h3 class="font-bold text-gray-900 dark:text-white mb-6">Optical Power Trend (Rx)</h3>
                <div class="h-[400px]">
                    <canvas id="signalChart"></canvas>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-8 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white">Recent Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-8 py-4 font-bold text-gray-400">Timestamp</th>
                                <th class="px-8 py-4 font-bold text-gray-400">Rx Power</th>
                                <th class="px-8 py-4 font-bold text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($logs->reverse()->take(50) as $log)
                                <tr>
                                    <td class="px-8 py-4 text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-8 py-4 font-mono font-bold {{ $log->rx_power < -27 ? 'text-rose-500' : 'text-emerald-500' }}">
                                        {{ $log->rx_power }} dBm
                                    </td>
                                    <td class="px-8 py-4">
                                        <span class="uppercase text-[10px] font-bold px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('signalChart').getContext('2d');
        const data = @json($logs->map(fn($l) => ['t' => $l->created_at->format('Y-m-d H:i'), 'v' => $l->rx_power]));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.t),
                datasets: [{
                    label: 'Rx Power (dBm)',
                    data: data.map(d => d.v),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        reverse: true, // Higher dBm is better, but usually -20 is "higher" than -30
                        suggestedMin: -35,
                        suggestedMax: -10,
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)'
                        },
                        ticks: {
                            callback: function(value) { return value + ' dBm'; }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Power: ${context.parsed.y} dBm`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
