<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Sto;
use App\Models\Stb;
use Illuminate\Http\Request;

class LocationDataController extends Controller
{
    // === REGIONS ===
    public function regions()
    {
        $regions = Region::all();
        return view('locations.regions', compact('regions'));
    }

    public function storeRegion(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        Region::create($request->all());
        return redirect()->back()->with('success', 'Region added.');
    }

    public function destroyRegion(Region $region)
    {
        $region->delete();
        return redirect()->back()->with('success', 'Region removed.');
    }

    // === STOs ===
    public function stos()
    {
        $stos = Sto::with('region')->get();
        $regions = Region::all();
        return view('locations.stos', compact('stos', 'regions'));
    }

    public function storeSto(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'name' => 'required|string'
        ]);
        Sto::create($request->all());
        return redirect()->back()->with('success', 'STO added.');
    }

    public function destroySto(Sto $sto)
    {
        $sto->delete();
        return redirect()->back()->with('success', 'STO removed.');
    }

    // === STBs ===
    public function stbs()
    {
        $stbs = Stb::with('sto.region')->get();
        $stos = Sto::with('region')->get();
        return view('locations.stbs', compact('stbs', 'stos'));
    }

    public function storeStb(Request $request)
    {
        $request->validate([
            'sto_id' => 'required|exists:stos,id',
            'name' => 'required|string'
        ]);
        Stb::create($request->all());
        return redirect()->back()->with('success', 'STB added.');
    }

    public function destroyStb(Stb $stb)
    {
        $stb->delete();
        return redirect()->back()->with('success', 'STB removed.');
    }

    // === API ENDPOINTS FOR DROPDOWNS ===
    public function apiStos(Region $region)
    {
        return response()->json($region->stos);
    }

    public function apiStbs(Sto $sto)
    {
        return response()->json($sto->stbs);
    }
}
