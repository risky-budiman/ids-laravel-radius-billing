<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    /**
     * Display a listing of the changelogs.
     */
    public function index()
    {
        $currentVersion = app_version();
        $formattedVersion = str_starts_with($currentVersion, 'v') ? $currentVersion : "v{$currentVersion}";
        
        // Auto-sync current version into database if not yet present
        $exists = Changelog::where('version', $formattedVersion)
            ->orWhere('version', $currentVersion)
            ->exists();
            
        if (!$exists) {
            try {
                \Illuminate\Support\Facades\Artisan::call('app:generate-changelog');
            } catch (\Throwable $e) {}
        }

        $changelogs = Changelog::latest('id')->get();
        return view('changelog.index', compact('changelogs'));
    }

    /**
     * Store a newly created changelog in storage.
     */
    public function store(Request $request)
    {
        // Security check: Only administrators can create changelogs
        if (!auth()->user()->isAdministrator()) {
            abort(403);
        }

        $request->validate([
            'version' => 'required|string|unique:changelogs',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:feature,fix,improvement,security',
            'release_date' => 'required|date',
        ]);

        Changelog::create([
            'version' => $request->version,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'release_date' => $request->release_date,
        ]);

        return redirect()->back()->with('success', 'Changelog entry added successfully.');
    }

    /**
     * Remove the specified changelog from storage.
     */
    public function destroy(Changelog $changelog)
    {
        if (!auth()->user()->isAdministrator()) {
            abort(403);
        }

        $changelog->delete();
        return redirect()->back()->with('success', 'Changelog entry deleted successfully.');
    }
}
