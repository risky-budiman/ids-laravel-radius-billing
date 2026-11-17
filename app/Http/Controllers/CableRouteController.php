<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CableRoute;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\Network\RoadRouter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Jalur kabel di Peta Pelanggan: gambar manual / ikuti jalan, simpan, hapus, dan pengaturan server routing. */
class CableRouteController extends Controller
{
    private const NODE_MODELS = ['customer' => Customer::class, 'odp' => \App\Models\Odp::class, 'odc' => \App\Models\Odc::class, 'stb' => \App\Models\Stb::class, 'sto' => \App\Models\Sto::class];

    private function pathRules(string $key, int $max): array
    {
        return [$key => "required|array|min:2|max:{$max}", "{$key}.*" => 'required|array|size:2',
            "{$key}.*.0" => 'required|numeric|between:-90,90', "{$key}.*.1" => 'required|numeric|between:-180,180'];
    }

    private static function clean(array $path): array
    {
        return array_values(array_map(fn ($p) => [round((float) $p[0], 6), round((float) $p[1], 6)], $path));
    }

    /** Jalur mengikuti jalan melewati titik-titik yang diklik; ujungnya tetap di posisi induk & titik sebenarnya. */
    public function snap(Request $request, RoadRouter $router)
    {
        $points = self::clean($request->validate($this->pathRules('points', 25))['points']);
        try {
            $road = $router->route($points);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $path = $road['path'];
        // OSRM menempelkan ujung ke jalan terdekat: sambungkan lagi ke tiang/rumah sebenarnya
        if ($path[0] !== $points[0]) {
            array_unshift($path, $points[0]);
        }
        if (end($path) !== end($points)) {
            $path[] = end($points);
        }

        return response()->json(['success' => true, 'path' => $path, 'length' => CableRoute::lengthOf($path)]);
    }

    public function save(Request $request)
    {
        $data = $request->validate(['node_type' => ['required', Rule::in(CableRoute::NODE_TYPES)], 'node_id' => 'required|string|max:64',
            'mode' => 'nullable|in:manual,road'] + $this->pathRules('path', 5000));
        $node = (self::NODE_MODELS[$data['node_type']])::find($data['node_id']);
        if (! $node) {
            return response()->json(['success' => false, 'message' => 'Titik jaringan tidak ditemukan.'], 404);
        }
        $path = self::clean($data['path']);
        $route = CableRoute::updateOrCreate(['node_type' => $data['node_type'], 'node_id' => (string) $node->getKey()],
            ['path' => $path, 'length_m' => CableRoute::lengthOf($path), 'mode' => $data['mode'] ?? 'manual', 'updated_by' => auth()->id()]);
        $name = $node->name ?? $node->getKey();
        ActivityLog::log('cable_route_saved', "Jalur kabel {$data['node_type']} {$name} disimpan ({$route->length_m} m, {$route->mode}).");

        return response()->json(['success' => true, 'message' => "Jalur kabel disimpan · {$route->length_m} m.", 'length' => $route->length_m]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['node_type' => ['required', Rule::in(CableRoute::NODE_TYPES)], 'node_id' => 'required|string|max:64']);
        $n = CableRoute::where($data)->delete();
        if ($n) {
            ActivityLog::log('cable_route_deleted', "Jalur kabel {$data['node_type']} {$data['node_id']} dihapus (kembali garis lurus).");
        }

        return response()->json(['success' => true, 'message' => 'Jalur dihapus; garis kembali lurus.']);
    }

    /** Server routing jalan (administrator). Kosong = fitur "Ikuti jalan" nonaktif. */
    public function settings(Request $request)
    {
        $data = $request->validate(['routing_url' => ['nullable', 'url:http,https', 'max:255'], 'routing_profile' => 'required|in:driving,foot,bike']);
        Setting::updateOrCreate(['key' => RoadRouter::URL_KEY], ['value' => trim((string) ($data['routing_url'] ?? '')), 'type' => 'string']);
        Setting::updateOrCreate(['key' => RoadRouter::PROFILE_KEY], ['value' => $data['routing_profile'], 'type' => 'string']);

        if (! filled($data['routing_url'] ?? null)) {
            return back()->with('success', 'Ikuti jalan otomatis dinonaktifkan.');
        }
        // Langsung diuji agar masalah jaringan terlihat sekarang, bukan saat teknisi menggambar jalur
        $test = app(RoadRouter::class)->test();

        return $test['ok']
            ? back()->with('success', 'Server routing jalan disimpan. ' . $test['message'])
            : back()->with('error', 'Alamat disimpan, tetapi uji gagal: ' . $test['message']);
    }
}
