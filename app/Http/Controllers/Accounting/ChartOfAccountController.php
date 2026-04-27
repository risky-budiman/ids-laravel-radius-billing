<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index()
    {
        // Get top-level accounts with their children
        $accounts = ChartOfAccount::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->orderBy('code');
            }])
            ->orderBy('code')
            ->get();

        return view('accounting.coa.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:chart_of_accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        ChartOfAccount::create($validated);

        return back()->with('success', 'Account created successfully.');
    }
}
