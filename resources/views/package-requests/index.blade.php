@php
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $tone = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger', 'canceled' => 'badge-gray'];
    $tabs = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2>Pengajuan Ganti Paket</h2>
            <p class="page-subtitle">Diajukan dari halaman pelanggan; paket baru berlaku setelah disetujui admin/administrator.</p>
        </div>
    </x-slot>

    <div class="max-w-6xl space-y-6">
        @foreach(['success' => 'emerald', 'error' => 'rose'] as $key => $t)
            @if(session($key))
                <div class="rounded-xl border border-{{ $t }}-200 bg-{{ $t }}-50 px-4 py-3 text-sm text-{{ $t }}-800 dark:border-{{ $t }}-500/30 dark:bg-{{ $t }}-500/10 dark:text-{{ $t }}-300">{{ session($key) }}</div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('package-requests.index', ['status' => $key]) }}" class="{{ $status === $key ? 'btn-primary' : 'btn-secondary' }} btn-sm">
                    {{ $label }}@if($key === 'pending' && $pendingCount) ({{ $pendingCount }})@endif
                </a>
            @endforeach
        </div>

        <div class="card">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Pelanggan</th><th>Paket</th><th>Alasan</th><th>Diajukan</th><th>Status</th>@if($canReview)<th>Tindakan</th>@endif</tr></thead>
                    <tbody>
                        @forelse($requests as $r)
                            @php $diff = (float) ($r->toPackage?->price ?? 0) - (float) ($r->fromPackage?->price ?? 0); @endphp
                            <tr class="align-top">
                                <td>
                                    @if($r->customer)
                                        <a href="{{ route('customers.show', $r->customer) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $r->customer->name }}</a>
                                        <span class="block font-mono text-xs text-surface-500">{{ $r->customer->customer_code }}</span>
                                    @else — @endif
                                </td>
                                <td class="text-sm whitespace-nowrap">
                                    <span class="text-surface-500">{{ $r->fromPackage?->name ?? '—' }}</span> →
                                    <span class="font-medium text-surface-900 dark:text-white">{{ $r->toPackage?->name }}</span>
                                    <span class="block text-xs {{ $diff >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $diff >= 0 ? '+' : '−' }}{{ $rp(abs($diff)) }} / bulan</span>
                                </td>
                                <td class="text-xs max-w-xs">{{ $r->reason }}
                                    @if($r->review_note)<span class="block mt-1 text-surface-500">Catatan admin: {{ $r->review_note }}</span>@endif
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $r->created_at->locale('id')->translatedFormat('d M Y H:i') }}<span class="block text-surface-500">{{ $r->requester?->name ?? '—' }}</span></td>
                                <td><span class="{{ $tone[$r->status] ?? 'badge-gray' }}">{{ \App\Models\PackageChangeRequest::STATUS_LABELS[$r->status] ?? $r->status }}</span>
                                    @if($r->reviewer)<span class="block text-xs text-surface-500 mt-1">{{ $r->reviewer->name }}</span>@endif
                                </td>
                                @if($canReview)
                                    <td>
                                        @if($r->status === 'pending')
                                            <div class="flex flex-col gap-1">
                                                <form method="POST" action="{{ route('package-requests.approve', $r) }}">
                                                    @csrf
                                                    <button class="btn-primary btn-sm w-full" onclick="return confirm('Setujui & ganti paket {{ $r->customer?->name }} ke {{ $r->toPackage?->name }} sekarang? Sesi internetnya akan diputus agar kecepatan baru berlaku.')">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('package-requests.reject', $r) }}"
                                                      onsubmit="const n = prompt('Alasan penolakan:'); if (!n) return false; this.note.value = n;">
                                                    @csrf
                                                    <input type="hidden" name="note">
                                                    <button class="btn-ghost btn-sm w-full">Tolak</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canReview ? 6 : 5 }}" class="py-10 text-center text-sm text-surface-500">Tidak ada pengajuan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())<div class="card-body">{{ $requests->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
