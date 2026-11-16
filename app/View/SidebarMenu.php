<?php

namespace App\View;

use App\Models\User;

/**
 * Definisi menu sidebar dalam satu tempat.
 *
 * Setiap section berisi item bertipe:
 *  - link : ['label', 'route', 'params'?, 'icon', 'active' => [pola routeIs], 'badge'?]
 *  - group: ['label', 'icon', 'children' => [link...]]  (tanpa ikon untuk anak)
 *
 * Item yang tidak boleh dilihat role tertentu tidak dimasukkan sama sekali,
 * dan section yang kosong otomatis dibuang.
 */
class SidebarMenu
{
    public static function customer(): array
    {
        return self::finalize([
            ['label' => null, 'items' => [
                self::link('Dashboard', 'customer.dashboard', 'home'),
                self::link('Tagihan Saya', 'customer.invoices', 'document'),
                self::link('Booster & Add-on', 'customer.boosters', 'bolt'),
                self::link('Lapor Gangguan', 'customer.tickets.index', 'support', ['customer.tickets.*']),
            ]],
        ]);
    }

    public static function admin(?User $u): array
    {
        if (! $u) {
            return [];
        }

        $isAdministrator = $u->isAdministrator();
        $isAdmin = $u->isAdmin(); // administrator + admin
        $isTeknisi = $u->isTeknisi();
        $isKasir = $u->isKasir();
        $isSales = $u->isSales();
        $isMitra = $u->isMitra();
        $isCs = $u->isCs();

        $sections = [];

        $sections[] = ['label' => null, 'items' => [
            $isMitra
                ? self::link('Dashboard', 'partners.show', 'home', ['partners.show'], [$u->id])
                : self::link('Dashboard', 'dashboard', 'home'),
        ]];

        // ---- Network Operations
        $sections[] = ['label' => 'Operasional Jaringan', 'items' => self::when($isTeknisi || $isAdministrator, [
            self::group('NOC Center', 'chart-bar', [
                self::link('Ringkasan Jaringan', 'noc.index'),
                self::link('Sinyal Optik', 'noc.signals', null, ['noc.signals', 'noc.history']),
                self::link('Pencarian ONU Massal', 'noc.discovery'),
            ]),
            self::link('Data OLT', 'olts.index', 'server', ['olts.*']),
            self::link('GenieACS', 'acs-servers.devices', 'chip', ['acs-servers.devices', 'acs-servers.device-details']),
        ])];

        // ---- Management
        $management = [];
        if ($isSales) {
            $management[] = self::link('Dashboard Sales', 'sales.dashboard', 'bolt');
            $management[] = self::link('Referal Saya', 'sales.customers', 'users');
            $management[] = self::link('Dompet & Mutasi Saya', 'sales.ledger', 'cash');
        }
        if ($isAdmin || $isSales || $isTeknisi || $isCs) {
            $management[] = self::link('Pelanggan', 'customers.index', 'users', [
                'customers.index', 'customers.create', 'customers.edit', 'customers.show',
                'customers.import.*', 'customers.activate', 'customers.dismantle',
            ]);
        }
        if (get_setting('enable_partner_module') == '1' && ($isAdmin || $isMitra)) {
            $management[] = $isMitra
                ? self::link('Mitra & Reseller', 'partners.show', 'user-group', ['partners.*'], [$u->id])
                : self::link('Mitra & Reseller', 'partners.index', 'user-group', ['partners.*']);
        }
        if ($isAdministrator) {
            $management[] = self::link('Paket', 'packages.index', 'cube', ['packages.*']);
            $management[] = self::link('Router (NAS)', 'nas.index', 'server', ['nas.*']);
        }
        if ($isAdmin || $isTeknisi || $isSales || $isCs) {
            $management[] = self::link('Tiket', 'tickets.index', 'ticket', ['tickets.index', 'tickets.create', 'tickets.show', 'tickets.edit']);
        }
        if ($isAdmin || $isCs) {
            $pending = \App\Models\PackageChangeRequest::where('status', 'pending')->count();
            $management[] = self::link('Pengajuan Ganti Paket', 'package-requests.index', 'refresh', ['package-requests.*'], [], $pending ? (string) $pending : null);
        }
        if ($isCs) {
            $management[] = self::link('Peta Pelanggan', 'customers.map', 'map');
        }
        if ($isAdmin) {
            $management[] = self::link('Rekap Tiket', 'tickets.recap', 'chart-bar', ['tickets.recap*']);
        }
        $sections[] = ['label' => 'Manajemen', 'items' => $management];

        // ---- Billing & Status
        $billing = [];
        if ($isCs) {
            $billing[] = self::link('Invoice', 'invoices.index', 'document', ['invoices.*']);
        }
        if ($isAdmin || $isKasir) {
            $billing[] = self::link('Invoice', 'invoices.index', 'document', ['invoices.*']);
            $billing[] = self::link('Piutang', 'receivables.index', 'receipt', ['receivables.*']);
            $billing[] = self::link('Desain Invoice', 'invoice-templates.index', 'template', ['invoice-templates.*']);
        }
        if ($isAdmin || $isTeknisi || $isCs) {
            $billing[] = self::link('Pengguna Online', 'online-users.index', 'signal', ['online-users.*']);
            $billing[] = self::link('Log Autentikasi', 'auth-logs.index', 'shield-check', ['auth-logs.*']);
        }
        if ($isCs) {
            $billing[] = self::link('Riwayat Pesan WhatsApp', 'whatsapp-logs.index', 'inbox', ['whatsapp-logs.*']);
        }
        $sections[] = ['label' => 'Tagihan & Status', 'items' => $billing];

        // ---- Finance & Accounting
        $finance = [];
        if ($isAdmin || $isKasir) {
            $finance[] = self::link('Kas & Bank', 'bank-accounts.index', 'credit-card', ['bank-accounts.*', 'bank-transactions.*']);
            $finance[] = self::link('Rekening Koran', 'bank-statement.index', 'receipt', ['bank-statement.*']);
        }
        // Route accounting.* hanya untuk administrator & admin.
        if ($isAdmin) {
            $finance[] = self::link('Mutasi Bank', 'bank-mutations.index', 'inbox', ['bank-mutations.*']);
            $finance[] = self::link('KPI Bisnis', 'kpi.index', 'trending-up', ['kpi.*']);
            $finance[] = self::link('Bagan Akun (COA)', 'accounting.coa.index', 'book', ['accounting.coa.*']);
            $finance[] = self::group('Laporan Keuangan', 'presentation', [
                self::link('Jurnal Umum', 'accounting.journals.index', null, ['accounting.journals.*']),
                self::link('Laba Rugi', 'accounting.reports.profit-loss'),
                self::link('Neraca', 'accounting.reports.balance-sheet'),
                self::link('Arus Kas', 'accounting.reports.cash-flow'),
                self::link('Pusat Pajak PPN', 'accounting.taxes.index', null, ['accounting.taxes.*', 'accounting.reports.tax-summary', 'accounting.tax-settings*']),
                self::link('Tutup Buku', 'accounting.closing.index', null, ['accounting.closing*']),
            ]);
            $finance[] = self::link('Komisi Sales', 'sales-commissions.index', 'cash', ['sales-commissions.*']);
        }
        $sections[] = ['label' => 'Keuangan & Akuntansi', 'items' => $finance];

        // ---- Inventory & Assets
        $sections[] = ['label' => 'Inventaris & Aset', 'items' => self::when($isAdmin || $isTeknisi, [
            self::group('Gudang', 'archive', [
                self::link('Stok Barang', 'inventory.index', null, ['inventory.index', 'inventory.show', 'inventory.create', 'inventory.edit']),
                self::link('Pemasok', 'suppliers.index', null, ['suppliers.*']),
                self::link('Pesanan Pembelian (PO)', 'purchase-orders.index', null, ['purchase-orders.*']),
                self::link('Kategori Barang', 'inventory.categories'),
                self::link('Barang Masuk', 'inventory.stock-in'),
                self::link('Barang Keluar', 'inventory.stock-out'),
                self::link('Audit Barang Keluar', 'inventory.outflow'),
            ]),
            self::link('Aset Tetap', 'fixed-assets.index', 'office', ['fixed-assets.*']),
            self::link('Peta Pelanggan', 'customers.map', 'map'),
        ])];

        // ---- Administrator only
        if ($isAdministrator) {
            $sections[] = ['label' => 'Pengaturan & Keamanan', 'items' => [
                self::link('Kelola Staf', 'users.index', 'user-cog', ['users.*']),
                self::link('Pengaturan Aplikasi', 'settings.index', 'settings', ['settings.index', 'settings.update']),
                self::link('Invoice & Portal', 'settings.company', 'receipt', ['settings.company*']),
                self::link('Pengaturan Isolir', 'settings.isolation', 'shield-check', ['settings.isolation*']),
                self::link('Pengaturan Instalasi', 'settings.installation', 'document', ['settings.installation*']),
                self::group('Data Master', 'globe', [
                    self::link('Data Wilayah', 'locations.regions'),
                    self::link('Data STO', 'locations.stos'),
                    self::link('Data STB', 'locations.stbs'),
                    self::link('Data ODC', 'locations.odcs'),
                    self::link('Data ODP', 'locations.odps'),
                ]),
                self::group('Integrasi', 'puzzle', [
                    self::link('Payment Gateway', 'integrations.payment'),
                    self::link('WhatsApp Gateway', 'integrations.whatsapp'),
                    self::link('Server GenieACS', 'acs-servers.index', null, ['acs-servers.index', 'acs-servers.create', 'acs-servers.edit']),
                    self::link('Bot NOC', 'integrations.noc-bot'),
                ]),
            ]];

            $sections[] = ['label' => 'WhatsApp', 'items' => [
                self::link('Template Pesan', 'whatsapp-templates.index', 'chat', ['whatsapp-templates.*']),
                self::link('Broadcast Pesan', 'whatsapp-broadcast.create', 'megaphone', ['whatsapp-broadcast.*']),
                self::link('Riwayat Pesan', 'whatsapp-logs.index', 'inbox', ['whatsapp-logs.*']),
            ]];

            $sections[] = ['label' => 'Audit Sistem', 'items' => [
                self::link('Log Aktivitas', 'activity-logs.index', 'clock', ['activity-logs.*']),
                self::link('Sistem & Log', 'server-logs.index', 'terminal', ['server-logs.*'], [], 'Pembaruan'),
            ]];
        }

        // ---- Application
        $app = [self::link('Catatan Rilis', 'changelog.index', 'book-open', ['changelog.*'])];
        if ($isAdministrator) {
            $app[] = self::link('Backup & Restore', 'backup.index', 'database', ['backup.*']);
        }
        $sections[] = ['label' => 'Aplikasi', 'items' => $app];

        return self::finalize($sections);
    }

    // ------------------------------------------------------------------

    protected static function link(string $label, string $route, ?string $icon = null, ?array $active = null, array $params = [], ?string $badge = null): array
    {
        return [
            'type' => 'link',
            'label' => $label,
            'route' => $route,
            'params' => $params,
            'icon' => $icon,
            'active' => $active ?? [$route],
            'badge' => $badge,
        ];
    }

    protected static function group(string $label, string $icon, array $children): array
    {
        return ['type' => 'group', 'label' => $label, 'icon' => $icon, 'children' => $children];
    }

    protected static function when(bool $condition, array $items): array
    {
        return $condition ? $items : [];
    }

    /**
     * Buang route yang tidak terdaftar & section kosong, lalu tandai item aktif.
     */
    protected static function finalize(array $sections): array
    {
        $request = request();

        $resolve = function (array $link) use ($request) {
            if (! \Illuminate\Support\Facades\Route::has($link['route'])) {
                return null;
            }
            $link['url'] = route($link['route'], $link['params']);
            $link['isActive'] = $request->routeIs(...$link['active']);

            return $link;
        };

        $out = [];
        foreach ($sections as $section) {
            $items = [];
            foreach ($section['items'] as $item) {
                if ($item['type'] === 'group') {
                    $children = array_values(array_filter(array_map($resolve, $item['children'])));
                    if (! $children) {
                        continue;
                    }
                    $item['children'] = $children;
                    $item['isActive'] = collect($children)->contains('isActive', true);
                    $items[] = $item;
                } elseif ($link = $resolve($item)) {
                    $items[] = $link;
                }
            }
            if ($items) {
                $out[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $out;
    }
}
