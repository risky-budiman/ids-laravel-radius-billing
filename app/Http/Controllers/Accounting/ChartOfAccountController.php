<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index()
    {
        // Get all accounts ordered by code and group by parent_id
        $allAccounts = ChartOfAccount::orderBy('code')->get();
        $groupedAccounts = $allAccounts->groupBy('parent_id');
        
        // Root accounts have parent_id null (or empty string/0 depending on DB state, usually null)
        $rootAccounts = $groupedAccounts->get('') ?: $groupedAccounts->get(null) ?: collect();

        return view('accounting.coa.index', [
            'rootAccounts' => $rootAccounts,
            'groupedAccounts' => $groupedAccounts
        ]);
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

    public function update(Request $request, ChartOfAccount $coa)
    {
        $validated = $request->validate([
            'code' => 'required|unique:chart_of_accounts,code,' . $coa->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        // Prevent circular dependency
        if ($validated['parent_id'] == $coa->id) {
            return back()->with('error', 'An account cannot be its own parent.');
        }

        $coa->update($validated);

        return back()->with('success', 'Account updated successfully.');
    }

    public function destroy(ChartOfAccount $coa)
    {
        // Check if has children
        if ($coa->children()->count() > 0) {
            return back()->with('error', 'Cannot delete account that has sub-accounts.');
        }

        // Check if has journal items
        if ($coa->journalItems()->count() > 0) {
            return back()->with('error', 'Cannot delete account that has transactions.');
        }

        $coa->delete();

        return back()->with('success', 'Account deleted successfully.');
    }
}
