<?php

namespace App\Http\Controllers;

use App\Models\Radius\Nas;
use Illuminate\Http\Request;

class NasController extends Controller
{
    public function index()
    {
        $routers = Nas::paginate(10);
        $serverIp = request()->server('SERVER_ADDR');
        if (!$serverIp || $serverIp === '127.0.0.1' || $serverIp === '::1') {
            $serverIp = gethostbyname(gethostname()) ?: '192.168.1.100';
        }
        return view('nas.index', compact('routers', 'serverIp'));
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
        $serverIp = request()->server('SERVER_ADDR');
        if (!$serverIp || $serverIp === '127.0.0.1' || $serverIp === '::1') {
            $serverIp = gethostbyname(gethostname()) ?: '192.168.1.100';
        }
        return view('nas.edit', ['router' => $na, 'serverIp' => $serverIp]);
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
