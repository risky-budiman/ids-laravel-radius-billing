<?php

namespace App\Http\Controllers;

use App\Models\Radius\Nas;
use Illuminate\Http\Request;

class NasController extends Controller
{
    public function index()
    {
        $routers = Nas::paginate(10);
        return view('nas.index', compact('routers'));
    }

    public function create()
    {
        return view('nas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nasname' => 'required|string|max:128', // IP Address usually
            'shortname' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer',
            'secret' => 'required|string|max:60',
            'server' => 'nullable|string|max:64',
            'community' => 'nullable|string|max:50',
            'description' => 'required|string|max:200',
        ]);

        Nas::create($validated);
        return redirect()->route('nas.index')->with('success', 'Router/NAS added successfully.');
    }

    public function edit(Nas $na) // Laravel translates NAS to Na but we can just use $router
    {
        return view('nas.edit', ['router' => $na]);
    }

    public function update(Request $request, Nas $na)
    {
        $validated = $request->validate([
            'nasname' => 'required|string|max:128',
            'shortname' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer',
            'secret' => 'required|string|max:60',
            'server' => 'nullable|string|max:64',
            'community' => 'nullable|string|max:50',
            'description' => 'required|string|max:200',
        ]);

        $na->update($validated);
        return redirect()->route('nas.index')->with('success', 'Router/NAS updated successfully.');
    }

    public function destroy(Nas $na)
    {
        $na->delete();
        return redirect()->route('nas.index')->with('success', 'Router/NAS removed successfully.');
    }
}
