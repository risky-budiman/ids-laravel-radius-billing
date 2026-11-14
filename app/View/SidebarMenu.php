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

        $sections = [];

        $sections[] = ['label' => null, 'items' => [
            self::link('Dashboard', 'dashboard', 'home'),
        ]];

        // ---- Network Operations
        $sections[] = ['label' => 'Network Operations', 'items' => self::when($isTeknisi || $isAdministrator, [
            self::group('NOC Center', 'chart-bar', [
                self::link('Network Overview', 'noc.index'),
                self::link('Optical Signals', 'noc.signals', null, ['noc.signals', 'noc.history']),
                self::link('Mass Discovery', 'noc.discovery'),
            ]),
            self::link('OLT Master', 'olts.index', 'server', ['olts.*']),
            self::link('GenieACS', 'acs-servers.devices', 'chip', ['acs-servers.devices', 'acs-servers.device-details']),
        ])];

        // ---- Management
        $management = [];
        if ($isSales) {
            $management[] = self::link('Sales Dashboard', 'sales.dashboard', 'bolt');
            $management[] = self::link('My Referrals', 'sales.customers', 'users');
            $management[] = self::link('My Wallet / Ledger', 'sales.ledger', 'cash');
        }
        if ($isAdmin || $isSales || $isTeknisi) {
            $management[] = self::link('Subscribers', 'customers.index', 'users', [
                'customers.index', 'customers.create', 'customers.edit', 'customers.show',
                'customers.import.*', 'customers.activate', 'customers.dismantle',
            ]);
        }
        if (get_setting('enable_partner_module') == '1' && ($isAdmin || $isMitra)) {
            $management[] = $isMitra
                ? self::link('Partners & Resellers', 'partners.show', 'user-group', ['partners.*'], [$u->id])
                : self::link('Partners & Resellers', 'partners.index', 'user-group', ['partners.*']);
        }
        if ($isAdministrator) {
            $management[] = self::link('Packages', 'packages.index', 'cube', ['packages.*']);
            $management[] = self::link('Routers (NAS)', 'nas.index', 'server', ['nas.*']);
        }
        if ($isAdmin || $isTeknisi || $isSales) {
            $management[] = self::link('Tickets', 'tickets.index', 'ticket', ['tickets.*']);
        }
        $sections[] = ['label' => 'Management', 'items' => $management];

        // ---- Billing & Status
        $billing = [];
        if ($isAdmin || $isKasir) {
            $billing[] = self::link('Invoices', 'invoices.index', 'document', ['invoices.*']);
            $billing[] = self::link('Invoice Builder', 'invoice-templates.index', 'template', ['invoice-templates.*']);
        }
        if ($isAdmin || $isTeknisi) {
            $billing[] = self::link('Online Users', 'online-users.index', 'signal', ['online-users.*']);
            $billing[] = self::link('Auth Logs', 'auth-logs.index', 'shield-check', ['auth-logs.*']);
        }
        $sections[] = ['label' => 'Billing & Status', 'items' => $billing];

        // ---- Finance & Accounting
        $finance = [];
        if ($isAdmin || $isKasir) {
            $finance[] = self::link('Kas & Bank', 'bank-accounts.index', 'credit-card', ['bank-accounts.*', 'bank-transactions.*']);
        }
        // Route accounting.* hanya untuk administrator & admin.
        if ($isAdmin) {
            $finance[] = self::link('Chart of Accounts', 'accounting.coa.index', 'book', ['accounting.coa.*']);
            $finance[] = self::group('Laporan Keuangan', 'presentation', [
                self::link('General Ledger', 'accounting.journals.index', null, ['accounting.journals.*']),
                self::link('Profit & Loss', 'accounting.reports.profit-loss'),
                self::link('Balance Sheet', 'accounting.reports.balance-sheet'),
                self::link('Cash Flow', 'accounting.reports.cash-flow'),
                self::link('Pusat Pajak PPN', 'accounting.taxes.index', null, ['accounting.taxes.*', 'accounting.reports.tax-summary', 'accounting.tax-settings*']),
                self::link('Closing Period', 'accounting.closing.index', null, ['accounting.closing*']),
            ]);
            $finance[] = self::link('Sales Force Ledger', 'sales-commissions.index', 'cash', ['sales-commissions.*']);
        }
        $sections[] = ['label' => 'Finance & Accounting', 'items' => $finance];

        // ---- Inventory & Assets
        $sections[] = ['label' => 'Inventory & Assets', 'items' => self::when($isAdmin || $isTeknisi, [
            self::group('Warehouse', 'archive', [
                self::link('Product Stock', 'inventory.index', null, ['inventory.index', 'inventory.show', 'inventory.create', 'inventory.edit']),
                self::link('Suppliers', 'suppliers.index', null, ['suppliers.*']),
                self::link('Purchase Orders', 'purchase-orders.index', null, ['purchase-orders.*']),
                self::link('Categories', 'inventory.categories'),
                self::link('Stock In', 'inventory.stock-in'),
                self::link('Issue Item (Out)', 'inventory.stock-out'),
                self::link('Audit Outflow', 'inventory.outflow'),
            ]),
            self::link('Fixed Assets', 'fixed-assets.index', 'office', ['fixed-assets.*']),
            self::link('Customer Maps', 'customers.map', 'map'),
        ])];

        // ---- Administrator only
        if ($isAdministrator) {
            $sections[] = ['label' => 'Settings & Security', 'items' => [
                self::link('Manage Staff', 'users.index', 'user-cog', ['users.*']),
                self::group('Data Master', 'globe', [
                    self::link('Regional Data', 'locations.regions'),
                    self::link('STO Data', 'locations.stos'),
                    self::link('STB Data', 'locations.stbs'),
                    self::link('ODC Data', 'locations.odcs'),
                    self::link('ODP Data', 'locations.odps'),
                    self::link('System Settings', 'settings.index', null, ['settings.*']),
                ]),
                self::group('Integrations', 'puzzle', [
                    self::link('Payment Gateways', 'integrations.payment'),
                    self::link('WhatsApp Gateway', 'integrations.whatsapp'),
                    self::link('GenieACS Servers', 'acs-servers.index', null, ['acs-servers.index', 'acs-servers.create', 'acs-servers.edit']),
                    self::link('NOC Bot Integration', 'integrations.noc-bot'),
                ]),
            ]];

            $sections[] = ['label' => 'WhatsApp', 'items' => [
                self::link('Template Pesan', 'whatsapp-templates.index', 'chat', ['whatsapp-templates.*']),
                self::link('Broadcast Pesan', 'whatsapp-broadcast.create', 'megaphone', ['whatsapp-broadcast.*']),
                self::link('Riwayat Pesan', 'whatsapp-logs.index', 'inbox', ['whatsapp-logs.*']),
            ]];

            $sections[] = ['label' => 'System Audit', 'items' => [
                self::link('Activity Logs', 'activity-logs.index', 'clock', ['activity-logs.*']),
                self::link('System & Logs', 'server-logs.index', 'terminal', ['server-logs.*'], [], 'Update'),
            ]];
        }

        // ---- Application
        $app = [self::link('App Changelog', 'changelog.index', 'book-open', ['changelog.*'])];
        if ($isAdministrator) {
            $app[] = self::link('Backup & Restore', 'backup.index', 'database', ['backup.*']);
        }
        $sections[] = ['label' => 'Application', 'items' => $app];

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
