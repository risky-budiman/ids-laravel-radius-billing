<?php

namespace App\Http\Controllers;

use App\Models\NetworkIncident;
use App\Models\Odp;
use App\Models\Olt;
use App\Services\Network\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** NOC Center › Gangguan Massal. */
class IncidentController extends Controller
{
    public function __construct(private IncidentService $incidents) {}

    public function index(Request $request)
    {
        $status = in_array($request->get('status'), ['open', 'resolved'], true) ? $request->get('status') : null;
        $items = NetworkIncident::query()
            ->withCount(['customers', 'tickets'])
            ->with(['ticket:id,ticket_number,status', 'creator:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'open' DESC")->latest('started_at')
            ->paginate(20)->withQueryString();
        $openCount = NetworkIncident::where('status', 'open')->count();

        return view('noc.incidents.index', compact('items', 'status', 'openCount'));
    }

    public function create(Request $request)
    {
        $olts = Olt::where('is_active', true)->with(['ponPorts' => fn ($q) => $q->orderBy('slot')->orderBy('pon_port')])->orderBy('name')->get();
        $odps = Odp::orderBy('name')->get(['id', 'name']);
        $prefill = $request->only(['scope_type', 'scope_id', 'olt']);

        return view('noc.incidents.create', compact('olts', 'odps', 'prefill'));
    }

    /** Pratinjau pelanggan terdampak (JSON) sebelum membuat gangguan. */
    public function preview(Request $request)
    {
        $data = $request->validate(['scope_type' => ['required', Rule::in(array_keys(NetworkIncident::SCOPES))], 'scope_id' => 'required|alpha_dash|max:64']);
        try {
            ['label' => $label, 'customers' => $customers] = $this->incidents->affected($data['scope_type'], $data['scope_id']);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Cakupan tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'label' => $label, 'count' => $customers->count(),
            'with_phone' => $customers->filter(fn ($c) => filled($c->phone))->count(),
            'sample' => $customers->take(12)->map(fn ($c) => ['name' => $c->name, 'code' => $c->customer_code])->values()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'scope_type' => ['required', Rule::in(array_keys(NetworkIncident::SCOPES))], 'scope_id' => 'required|alpha_dash|max:64',
            'title' => 'nullable|string|max:150', 'description' => 'nullable|string|max:2000', 'estimate' => 'nullable|string|max:100',
            'priority' => 'required|in:medium,high,urgent', 'notify' => 'nullable|boolean',
        ]);
        try {
            $incident = $this->incidents->open($data + ['notify' => $request->boolean('notify')], $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('noc.incidents.show', $incident)->with('success', "Gangguan massal dibuat: tiket induk {$incident->ticket?->ticket_number}, "
            . $incident->customers()->count() . ' pelanggan terdampak'
            . ($incident->notified_count ? ", {$incident->notified_count} WhatsApp pemberitahuan masuk antrean." : '.'));
    }

    public function show(NetworkIncident $incident)
    {
        $incident->load(['ticket', 'creator:id,name', 'resolver:id,name']);
        $customers = $incident->customers()->with(['package:id,name', 'signalCache:id,customer_id,status,rx_power'])->orderBy('name')->paginate(50);
        $tickets = $incident->tickets()->with('customer:id,name,customer_code')->whereKeyNot($incident->ticket_id)->latest()->get();

        return view('noc.incidents.show', compact('incident', 'customers', 'tickets'));
    }

    public function notify(NetworkIncident $incident)
    {
        abort_unless($incident->isOpen(), 422, 'Gangguan sudah pulih.');
        $n = $this->incidents->notify($incident, 'gangguan_masal', $incident->estimate ?: 'secepatnya');

        return back()->with($n ? 'success' : 'error', $n ? "{$n} WhatsApp pemberitahuan gangguan masuk antrean."
            : 'Tidak ada yang dikirim: template "Gangguan Massal" nonaktif atau tidak ada pelanggan aktif bernomor HP.');
    }

    public function resolve(Request $request, NetworkIncident $incident)
    {
        $data = $request->validate(['resolution_notes' => 'nullable|string|max:2000', 'notify' => 'nullable|boolean']);
        try {
            $r = $this->incidents->resolve($incident, $data['resolution_notes'] ?? null, $request->boolean('notify'), $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Gangguan ditandai pulih. {$r['children']} tiket pelanggan ikut diselesaikan"
            . ($r['notified'] ? ", {$r['notified']} WhatsApp pemulihan masuk antrean." : '.'));
    }
}
