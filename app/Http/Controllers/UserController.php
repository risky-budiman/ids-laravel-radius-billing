<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount(['sessions' => function($query) {
            $query->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->getTimestamp());
        }])->latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = User::STAFF_ROLES;
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(array_keys(User::STAFF_ROLES))],
            'commission_rate' => 'nullable|numeric|min:0',
            'commission_type' => 'nullable|in:percentage,fixed',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'is_sales' => 'nullable',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'commission_rate' => $request->commission_rate,
            'commission_type' => $request->commission_type ?: 'percentage',
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name' => $request->bank_account_name,
            'is_active' => true,
            'is_sales' => $request->has('is_sales') && self::canBeAlsoSales($request->role),
        ]);

        return redirect()->route('users.index')->with('success', 'Staff account created successfully.');
    }

    public function edit(User $user)
    {
        // Daftar memuat Administrator: dulu tidak ada, sehingga menyimpan form administrator lain menurunkannya jadi Admin
        $roles = User::STAFF_ROLES;
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(array_keys(User::STAFF_ROLES))],
            'commission_rate' => 'nullable|numeric|min:0',
            'commission_type' => 'nullable|in:percentage,fixed',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'is_sales' => 'nullable',
        ]);

        // Role akun sendiri tidak bisa diubah; administrator aktif terakhir tidak bisa diturunkan
        $role = $user->id === auth()->id() ? $user->role : $request->role;
        if ($role !== User::ROLE_ADMINISTRATOR && $user->isLastActiveAdministrator()) {
            return back()->withInput()->with('error', 'Ini administrator aktif terakhir; role-nya tidak bisa diubah.');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $role,
            'commission_rate' => $request->commission_rate,
            'commission_type' => $request->commission_type ?: ($user->commission_type ?: 'percentage'),
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name' => $request->bank_account_name,
            'is_sales' => $request->has('is_sales') && self::canBeAlsoSales($role),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $oldRole = $user->role;
        $user->update($data);

        if ($oldRole !== $role) {
            \App\Models\ActivityLog::log('user_role_changed', "Role akun {$user->name} diubah dari {$oldRole} menjadi {$role}.", $user);
        }

        return redirect()->route('users.index')->with('success', 'Akun staf berhasil diperbarui.');
    }

    /** "Juga sales" hanya untuk staf internal; mitra (pihak luar) & sales (sudah sales) tidak. */
    private static function canBeAlsoSales(?string $role): bool
    {
        return in_array($role, [User::ROLE_ADMINISTRATOR, User::ROLE_ADMIN, User::ROLE_TEKNISI, User::ROLE_KASIR, User::ROLE_CS], true);
    }

    public function toggleStatus(User $user)
    {
        // Prevent deactivating own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
        }
        if ($user->isLastActiveAdministrator()) {
            return back()->with('error', 'Administrator aktif terakhir tidak bisa dinonaktifkan.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Account has been successfully {$status}.");
    }

    public function resetSessions(User $user)
    {
        $user->sessions()->delete();
        return back()->with('success', "All active sessions for {$user->name} have been reset.");
    }

    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        if ($user->isLastActiveAdministrator()) {
            return back()->with('error', 'Administrator aktif terakhir tidak bisa dihapus.');
        }

        // Akun yang punya riwayat (tiket, komisi, jurnal, log, pelanggan referal...) tidak dihapus permanen:
        // dulu riwayat ikut terhapus/terputus. Nonaktifkan saja.
        $history = \App\Support\UserHistory::references($user);
        if ($history) {
            $detail = implode(', ', array_map(fn ($t, $n) => "{$t}: {$n}", array_keys($history), $history));

            return back()->with('error', "Akun {$user->name} punya riwayat ({$detail}). Nonaktifkan akun ini, jangan dihapus.");
        }

        $name = $user->name;
        $user->delete();
        \App\Models\ActivityLog::log('user_deleted', "Akun staf {$name} (tanpa riwayat) dihapus.");

        return redirect()->route('users.index')->with('success', 'Akun dihapus.');
    }
}
