<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PackageChangeRequest;
use App\Services\PackageChangeService;
use Illuminate\Http\Request;

/**
 * Pengajuan ganti paket: CS/admin mengajukan dari halaman pelanggan; admin/administrator
 * menyetujui atau menolak di daftar pengajuan. CS hanya melihat daftar.
 */
class PackageChangeRequestController extends Controller
{
    public function __construct(private PackageChangeService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $query = PackageChangeRequest::with(['customer:id,name,customer_code,status', 'fromPackage:id,name,price', 'toPackage:id,name,price', 'requester:id,name', 'reviewer:id,name'])
            ->latest('id');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('package-requests.index', [
            'requests' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'pendingCount' => PackageChangeRequest::where('status', 'pending')->count(),
            'canReview' => $request->user()->isAdmin(),
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'to_package_id' => 'required|integer|exists:packages,id',
            'reason' => 'required|string|max:500',
        ], [], ['to_package_id' => 'paket baru', 'reason' => 'alasan']);

        try {
            $this->service->request($customer, (int) $data['to_package_id'], $data['reason'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan ganti paket dikirim dan menunggu persetujuan admin.');
    }

    public function approve(Request $request, PackageChangeRequest $packageRequest)
    {
        try {
            $r = $this->service->approve($packageRequest, $request->user(), $request->input('note'));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Paket {$r->customer->name} diganti ke {$r->toPackage->name}.");
    }

    public function reject(Request $request, PackageChangeRequest $packageRequest)
    {
        $data = $request->validate(['note' => 'required|string|max:500'], [], ['note' => 'alasan penolakan']);
        try {
            $this->service->reject($packageRequest, $request->user(), $data['note']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
