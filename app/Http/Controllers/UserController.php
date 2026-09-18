<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List semua user.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('id')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Form tambah user baru.
     */
    public function create()
    {
        return view('users.form', ['user' => null, 'mode' => 'create']);
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_.]+$/', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'role'     => ['required', 'in:superadmin,admin'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            'name'     => $data['name'],
            'username' => strtolower($data['username']),
            'email'    => $data['email'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')
            ->with('success', "User \"{$data['name']}\" berhasil ditambahkan.");
    }

    /**
     * Form edit user.
     */
    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'mode' => 'edit']);
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_.]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', 'in:superadmin,admin'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name     = $data['name'];
        $user->username = strtolower($data['username']);
        $user->email    = $data['email'] ?? null;
        $user->role     = $data['role'];

        // Reset password (opsional)
        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
        }

        // Jangan biarkan superadmin terakhir didowngrade
        if ($user->role !== 'superadmin') {
            $superadminCount = User::where('role', 'superadmin')->where('id', '!=', $user->id)->count();
            if ($superadminCount === 0 && $user->getOriginal('role') === 'superadmin') {
                return back()->withErrors(['role' => 'Harus ada minimal 1 superadmin aktif.']);
            }
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        // Jangan hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
        }

        // Minimal 1 superadmin harus ada
        if ($user->isSuperAdmin()) {
            $superadminCount = User::where('role', 'superadmin')->count();
            if ($superadminCount <= 1) {
                return back()->with('error', 'Tidak bisa menghapus superadmin terakhir.');
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User \"{$name}\" berhasil dihapus.");
    }
}
