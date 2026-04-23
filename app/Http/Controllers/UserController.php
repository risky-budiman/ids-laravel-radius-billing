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
        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_TEKNISI => 'Teknisi',
            User::ROLE_KASIR => 'Kasir',
            User::ROLE_SALES => 'Sales'
        ];
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_TEKNISI, User::ROLE_KASIR, User::ROLE_SALES])],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'Staff account created successfully.');
    }

    public function edit(User $user)
    {
        // Prevent editing the main administrator if needed, or allow it
        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_TEKNISI => 'Teknisi',
            User::ROLE_KASIR => 'Kasir',
            User::ROLE_SALES => 'Sales'
        ];
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_TEKNISI, User::ROLE_KASIR, User::ROLE_SALES])],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Staff account updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        // Prevent deactivating own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
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
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Account deleted successfully.');
    }
}
