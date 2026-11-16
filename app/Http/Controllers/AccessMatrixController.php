<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RouteAccess;
use Illuminate\Support\Facades\Route;

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
        $roles = User::STAFF_ROLES;
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
            $isRead = $methods === ['GET'];
            $last = (string) last(explode('.', $name));
            $allowed = [];
            foreach (array_keys($roles) as $role) {
                $allowed[$role] = RouteAccess::roleAllows($role, $lists);
            }

            $features[$key] ??= ['label' => self::FEATURES[$key] ?? ucfirst(str_replace('-', ' ', $key)), 'routes' => []];
            $features[$key]['routes'][] = [
                'name' => $name, 'uri' => '/' . $route->uri(), 'method' => implode('|', $methods), 'read' => $isRead,
                'action' => self::ACTIONS[$last] ?? str_replace(['-', '_'], ' ', $last), 'allowed' => $allowed,
            ];
        }

        // Tingkat akses per fitur per role
        foreach ($features as &$f) {
            usort($f['routes'], fn ($a, $b) => [$b['read'], $a['name']] <=> [$a['read'], $b['name']]);
            foreach (array_keys($roles) as $role) {
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
            }
        }
        unset($f);
        uasort($features, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return view('access.matrix', ['features' => $features, 'roles' => $roles]);
    }
}
