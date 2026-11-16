<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Sumber tunggal hak akses staf: middleware `role:` pada rute. Sidebar, tombol di tampilan (@canroute) dan
 * halaman "Hak akses per role" membaca dari sini, sehingga yang ditampilkan selalu sama dengan yang ditegakkan.
 *
 * Rute bisa punya beberapa `role:` (grup bertingkat): semuanya harus lolos. Rute tanpa `role:` terbuka untuk
 * semua yang lolos middleware lain (mis. auth). Pemeriksaan tambahan di controller (mis. hanya administrator
 * untuk aksi tertentu) tidak terbaca di sini.
 */
class RouteAccess
{
    /** @var array<string, array<int, array<int, string>>|null> */
    private static array $cache = [];

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

    /** Apakah pengguna boleh membuka rute ini (mengikuti RoleMiddleware: termasuk penanda "juga sales"). */
    public static function allows(?User $user, string $routeName): bool
    {
        $lists = self::roleLists($routeName);
        if ($lists === null || ! $user) {
            return false;
        }
        foreach ($lists as $roles) {
            if (! $user->hasRole($roles)) {
                return false;
            }
        }

        return true;
    }

    /** Apakah role (tanpa penanda tambahan) boleh membuka rute ini. */
    public static function roleAllows(string $role, array $lists): bool
    {
        foreach ($lists as $roles) {
            if (! in_array($role, $roles, true)) {
                return false;
            }
        }

        return true;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
