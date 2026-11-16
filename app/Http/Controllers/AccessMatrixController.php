<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RouteAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

/**
 * Halaman "Hak akses per role": dibuat langsung dari middleware role pada rute (App\Support\RouteAccess),
 * jadi selalu sama dengan yang benar-benar ditegakkan aplikasi.
 */
class AccessMatrixController extends Controller
{
    /** Nama fitur per awalan nama rute. */
    private const FEATURES = [
        'dashboard' => 'Dashboard', 'customers' => 'Pelanggan', 'customer-contacts' => 'Kontak pelanggan',
        'invoices' => 'Invoice & penagihan', 'receivables' => 'Piutang', 'invoice-templates' => 'Desain invoice',
        'packages' => 'Paket', 'package-requests' => 'Pengajuan ganti paket', 'tickets' => 'Tiket', 'ticket-reports' => 'Rekap tiket',
        'olts' => 'OLT', 'noc' => 'NOC', 'acs-servers' => 'GenieACS', 'genieacs' => 'GenieACS', 'nas' => 'Router (NAS)',
        'online-users' => 'Pengguna online', 'auth-logs' => 'Log autentikasi', 'activity-logs' => 'Log aktivitas',
        'bank-accounts' => 'Kas & bank', 'bank-transactions' => 'Transaksi kas', 'bank-mutations' => 'Mutasi bank',
        'accounting' => 'Akuntansi', 'finance' => 'Keuangan', 'reports' => 'Laporan', 'kpi' => 'KPI bisnis',
        'inventory' => 'Inventaris', 'purchase-orders' => 'Purchase order', 'fixed-assets' => 'Aset tetap',
        'partners' => 'Mitra', 'sales' => 'Sales', 'admin' => 'Komisi sales', 'users' => 'Staf', 'access' => 'Hak akses',
        'settings' => 'Pengaturan', 'whatsapp-logs' => 'Log WhatsApp', 'whatsapp-broadcast' => 'Broadcast WhatsApp',
        'changelog' => 'Catatan rilis', 'docs' => 'Dokumentasi', 'profile' => 'Profil', 'map' => 'Peta pelanggan',
        'installation-reports' => 'Berita acara instalasi', 'backups' => 'Backup', 'backup' => 'Backup & restore',
        'import' => 'Import/Export', 'radius' => 'RADIUS (putus sesi massal)', 'sales-commissions' => 'Komisi sales',
        'suppliers' => 'Pemasok', 'bank-statement' => 'Rekening koran', 'server-logs' => 'Log server',
        'system' => 'Pembaruan sistem', 'whatsapp-templates' => 'Template WhatsApp', 'locations' => 'Wilayah & lokasi',
        'integrations' => 'Integrasi (pembayaran, WhatsApp, NOC)', 'api' => 'Pencarian & notifikasi (internal)',
    ];

    private const ACTIONS = [
        'index' => 'Lihat daftar', 'show' => 'Lihat detail', 'create' => 'Form tambah', 'store' => 'Tambah',
        'edit' => 'Form ubah', 'update' => 'Ubah', 'destroy' => 'Hapus',
    ];

    public function __invoke()
    {
        RouteAccess::flush();
        $roles = User::STAFF_ROLES;

        return view('access.matrix', ['features' => $this->features(), 'roles' => $roles,
            'overrideCount' => \App\Models\RolePermission::count()]);
    }

    /** Fitur → rute beserta akses bawaan, efektif & pengecualian per role. */
    private function features(): array
    {
        $roles = array_keys(User::STAFF_ROLES);
        $features = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($route->uri(), 'admin')) {
                continue;
            }
            $lists = RouteAccess::listsOf($route);
            if (! $lists) {
                continue;   // login, reset password, dll.
            }
            $key = explode('.', $name)[0];
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            $last = (string) last(explode('.', $name));
            // "Lihat" = halaman baca saja; form tambah/ubah (create/edit) dihitung sebagai aksi ubah
            $row = ['name' => $name, 'uri' => '/' . $route->uri(), 'method' => implode('|', $methods), 'read' => $methods === ['GET'] && ! in_array($last, ['create', 'edit'], true),
                'action' => self::ACTIONS[$last] ?? str_replace(['-', '_'], ' ', $last), 'locked' => RouteAccess::isLocked($name)];
            foreach ($roles as $role) {
                $row['default'][$role] = RouteAccess::defaultAllows($role, $lists);
                $row['override'][$role] = RouteAccess::overrideFor($role, $name);
                $row['allowed'][$role] = $row['override'][$role] ?? $row['default'][$role];
            }

            $features[$key] ??= ['label' => self::FEATURES[$key] ?? ucfirst(str_replace('-', ' ', $key)), 'routes' => []];
            $features[$key]['routes'][] = $row;
        }

        foreach ($features as &$f) {
            usort($f['routes'], fn ($a, $b) => [$b['read'], $a['name']] <=> [$a['read'], $b['name']]);
            $f['locked'] = collect($f['routes'])->every(fn ($r) => $r['locked']);
            foreach ($roles as $role) {
                $read = collect($f['routes'])->where('read', true);
                $write = collect($f['routes'])->where('read', false);
                $canRead = $read->filter(fn ($r) => $r['allowed'][$role])->count();
                $canWrite = $write->filter(fn ($r) => $r['allowed'][$role])->count();
                $f['level'][$role] = match (true) {
                    $canRead + $canWrite === 0 => 'none',
                    $canRead === $read->count() && $canWrite === $write->count() => 'full',
                    $canWrite === 0 && $canRead === $read->count() => 'view',
                    default => 'partial',
                };
                $f['changed'][$role] = collect($f['routes'])->contains(fn ($r) => $r['override'][$role] !== null);
            }
        }
        unset($f);
        uasort($features, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $features;
    }

    /**
     * Simpan pengecualian. Per fitur (level: default | full | view | none) atau per rute (value: default |
     * allow | deny). Role administrator & rute terkunci tidak bisa diubah.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(array_diff(array_keys(User::STAFF_ROLES), [User::ROLE_ADMINISTRATOR]))],
            'feature' => 'nullable|string|max:60',
            'level' => 'required_with:feature|nullable|in:default,full,view,none',
            'route' => 'required_without:feature|nullable|string|max:150',
            'value' => 'required_with:route|nullable|in:default,allow,deny',
        ], ['role.in' => 'Akses role administrator tidak bisa dibatasi.']);

        $role = $data['role'];
        $targets = [];   // route => true|false|null (null = kembali ke bawaan)
        if (! empty($data['route'])) {
            abort_unless(Route::has($data['route']), 422, 'Rute tidak dikenal.');
            $targets[$data['route']] = ['default' => null, 'allow' => true, 'deny' => false][$data['value']];
        } else {
            $feature = $this->features()[$data['feature']] ?? abort(422, 'Fitur tidak dikenal.');
            foreach ($feature['routes'] as $r) {
                $targets[$r['name']] = match ($data['level']) {
                    'default' => null,
                    'full' => true,
                    'none' => false,
                    'view' => $r['read'],
                };
            }
        }

        $changed = 0;
        foreach ($targets as $routeName => $allowed) {
            if (RouteAccess::isLocked($routeName)) {
                continue;
            }
            $lists = RouteAccess::roleLists($routeName) ?? [];
            // Sama dengan bawaan → tidak perlu pengecualian
            if ($allowed === null || $allowed === RouteAccess::defaultAllows($role, $lists)) {
                $changed += \App\Models\RolePermission::where('role', $role)->where('route', $routeName)->delete();

                continue;
            }
            \App\Models\RolePermission::updateOrCreate(['role' => $role, 'route' => $routeName],
                ['allowed' => $allowed, 'updated_by' => $request->user()->id]);
            $changed++;
        }
        RouteAccess::flush();

        $what = ! empty($data['route']) ? $data['route'] : ($data['feature'] . ' → ' . $data['level']);
        \App\Models\ActivityLog::log('role_permissions_updated', 'Hak akses role ' . (User::STAFF_ROLES[$role] ?? $role) . " diubah: {$what}", null,
            ['role' => $role, 'targets' => $targets]);

        $msg = 'Hak akses ' . (User::STAFF_ROLES[$role] ?? $role) . ' diperbarui.';
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'changed' => $changed]);
        }

        return back()->with('success', $msg);
    }

    /** Kembalikan semua (atau satu role) ke aturan bawaan. */
    public function reset(Request $request)
    {
        $role = $request->input('role');
        $q = \App\Models\RolePermission::query();
        if ($role) {
            $q->where('role', $role);
        }
        $n = $q->delete();
        RouteAccess::flush();
        \App\Models\ActivityLog::log('role_permissions_reset', 'Hak akses ' . ($role ? 'role ' . (User::STAFF_ROLES[$role] ?? $role) : 'semua role') . " dikembalikan ke bawaan ({$n} pengecualian dihapus).");

        return back()->with('success', "Hak akses dikembalikan ke bawaan ({$n} pengecualian dihapus).");
    }
}
