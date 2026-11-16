<?php

namespace App\Support;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber tunggal hak akses staf. Aturan bawaan = middleware `role:` pada rute; administrator dapat menambah
 * PENGECUALIAN per role (tabel role_permissions) di halaman "Hak Akses per Role". RoleMiddleware, sidebar,
 * tombol (@canroute) dan halaman hak akses semuanya membaca dari sini.
 *
 * Pengaman: role administrator tidak bisa dibatasi, dan rute sensitif (LOCKED) tidak bisa diberikan ke role
 * lain. Pemeriksaan tambahan di dalam controller (mis. kolom tertentu khusus administrator) tetap berlaku.
 */
class RouteAccess
{
    /** Rute yang selalu khusus administrator (tidak bisa diubah dari layar). */
    public const LOCKED_PREFIXES = ['access.', 'users.', 'backup.', 'system.'];

    /** @var array<string, array<int, array<int, string>>|null> */
    private static array $cache = [];

    /** @var array<string, array<string, bool>>|null  [role => [route => allowed]] */
    private static ?array $overrides = null;

    /** Daftar role per middleware `role:` pada rute (kosong = tanpa batasan role); null bila rute tidak ada. */
    public static function roleLists(string $routeName): ?array
    {
        if (array_key_exists($routeName, self::$cache)) {
            return self::$cache[$routeName];
        }
        $route = RouteFacade::getRoutes()->getByName($routeName);

        return self::$cache[$routeName] = $route ? self::listsOf($route) : null;
    }

    /** @return array<int, array<int, string>> */
    public static function listsOf(Route $route): array
    {
        $lists = [];
        foreach ($route->gatherMiddleware() as $m) {
            if (is_string($m) && str_starts_with($m, 'role:')) {
                $lists[] = array_values(array_filter(explode(',', substr($m, 5))));
            }
        }

        return $lists;
    }

    public static function isLocked(string $routeName): bool
    {
        foreach (self::LOCKED_PREFIXES as $p) {
            if (str_starts_with($routeName, $p)) {
                return true;
            }
        }

        return false;
    }

    /** Pengecualian yang tersimpan: [role => [route => allowed]]. */
    public static function overrides(): array
    {
        if (self::$overrides === null) {
            try {
                self::$overrides = Schema::hasTable('role_permissions')
                    ? RolePermission::query()->get(['role', 'route', 'allowed'])
                        ->groupBy('role')->map(fn ($rows) => $rows->pluck('allowed', 'route')->map(fn ($v) => (bool) $v)->all())->all()
                    : [];
            } catch (\Throwable) {
                self::$overrides = [];
            }
        }

        return self::$overrides;
    }

    /** Pengecualian untuk role & rute: true/false, atau null = ikut aturan bawaan. */
    public static function overrideFor(string $role, string $routeName): ?bool
    {
        if ($role === User::ROLE_ADMINISTRATOR || self::isLocked($routeName)) {
            return null;
        }

        return self::overrides()[$role][$routeName] ?? null;
    }

    /** Aturan bawaan untuk role (tanpa pengecualian). */
    public static function defaultAllows(string $role, array $lists): bool
    {
        foreach ($lists as $roles) {
            if (! in_array($role, $roles, true)) {
                return false;
            }
        }

        return true;
    }

    /** Akses efektif sebuah role (bawaan + pengecualian). */
    public static function roleAllows(string $role, array $lists, ?string $routeName = null): bool
    {
        $o = $routeName ? self::overrideFor($role, $routeName) : null;

        return $o ?? self::defaultAllows($role, $lists);
    }

    /**
     * Apakah pengguna boleh membuka rute ini. Sama dengan RoleMiddleware: role sendiri, ditambah akses sales
     * bagi staf yang ditandai "juga sales".
     */
    public static function allows(?User $user, string $routeName): bool
    {
        $lists = self::roleLists($routeName);
        if ($lists === null || ! $user) {
            return false;
        }

        return self::decide($user, $routeName, $lists);
    }

    /** Keputusan akses untuk pengguna pada rute dengan daftar role tertentu. */
    public static function decide(User $user, string $routeName, array $lists): bool
    {
        $role = (string) $user->role;
        $flag = $user->hasSalesFlag();
        $own = self::overrideFor($role, $routeName);
        $sales = $flag ? self::overrideFor(User::ROLE_SALES, $routeName) : null;

        if ($own === true || $sales === true) {
            return true;
        }
        if ($own === false) {
            return false;
        }

        // Aturan bawaan (sama dengan RoleMiddleware/User::hasRole): setiap middleware role harus lolos —
        // role sendiri, atau "juga sales" bila daftar memuat sales (kecuali akses sales ditolak administrator)
        foreach ($lists as $roles) {
            $ok = in_array($role, $roles, true) || ($flag && $sales !== false && in_array(User::ROLE_SALES, $roles, true));
            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    public static function flush(): void
    {
        self::$cache = [];
        self::$overrides = null;
    }
}
