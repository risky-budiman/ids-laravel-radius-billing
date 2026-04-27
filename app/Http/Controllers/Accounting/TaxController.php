<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::with('chartOfAccount')->get();
        return view('accounting.taxes.index', compact('taxes'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::whereIn('type', ['liability', 'asset'])->get();
        return view('accounting.taxes.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:taxes,code',
            'rate' => 'required|numeric|min:0|max:100',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        Tax::create($request->all());

        return redirect()->route('accounting.taxes.index')->with('success', 'Pajak berhasil ditambahkan.');
    }

    public function edit(Tax $tax)
    {
        $accounts = ChartOfAccount::whereIn('type', ['liability', 'asset'])->get();
        return view('accounting.taxes.edit', compact('tax', 'accounts'));
    }

    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:taxes,code,' . $tax->id,
            'rate' => 'required|numeric|min:0|max:100',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $tax->update($request->all());

        return redirect()->route('accounting.taxes.index')->with('success', 'Pajak berhasil diperbarui.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();
        return back()->with('success', 'Pajak berhasil dihapus.');
    }
}
