<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function index()
    {
        $assets = FixedAsset::latest()->paginate(15);
        return view('fixed-assets.index', compact('assets'));
    }

    public function create()
    {
        // Get all accounts (or filter by specific types if needed)
        $accounts = ChartOfAccount::all();
        return view('fixed-assets.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'asset_account_id' => 'required|exists:chart_of_accounts,id',
            'depreciation_account_id' => 'required|exists:chart_of_accounts,id',
            'accumulated_account_id' => 'required|exists:chart_of_accounts,id',
            'accumulated_depreciation' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $assetCode = 'FA-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -4));

        FixedAsset::create([
            'name' => $validated['name'],
            'asset_code' => $assetCode,
            'purchase_date' => $validated['purchase_date'],
            'purchase_price' => $validated['purchase_price'],
            'salvage_value' => $validated['salvage_value'],
            'useful_life_months' => $validated['useful_life_months'],
            'accumulated_depreciation' => $validated['accumulated_depreciation'] ?? 0,
            'status' => 'active',
            'asset_account_id' => $validated['asset_account_id'],
            'depreciation_account_id' => $validated['depreciation_account_id'],
            'accumulated_account_id' => $validated['accumulated_account_id'],
            'description' => $validated['description'],
        ]);

        return redirect()->route('fixed-assets.index')->with('success', 'Aset Tetap berhasil ditambahkan.');
    }

    public function edit(FixedAsset $fixedAsset)
    {
        $accounts = ChartOfAccount::all();
        return view('fixed-assets.edit', compact('fixedAsset', 'accounts'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'salvage_value' => 'required|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'status' => 'required|in:active,disposed',
            'description' => 'nullable|string',
        ]);

        $fixedAsset->update($validated);

        return redirect()->route('fixed-assets.index')->with('success', 'Data Aset Tetap berhasil diperbarui.');
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        if ($fixedAsset->accumulated_depreciation > 0) {
            return back()->with('error', 'Aset tidak bisa dihapus karena sudah memiliki riwayat penyusutan. Silakan ubah statusnya menjadi Disposed.');
        }

        $fixedAsset->delete();
        return redirect()->route('fixed-assets.index')->with('success', 'Aset Tetap berhasil dihapus.');
    }
}
