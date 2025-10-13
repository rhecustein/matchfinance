<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Display system-wide users (super admin view)
     */
    public function systemIndex(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $query = User::with('company');

        // Filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)->where('is_suspended', false);
            } elseif ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('admin.system-users.index', compact('users', 'companies'));
    }

    /**
     * Display specified user (system view)
     */
    public function systemShow(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $user->load([
            'company',
            'bankStatements' => fn($q) => $q->latest()->limit(10),
            'verifiedTransactions' => fn($q) => $q->latest()->limit(10),
        ]);

        return view('admin.system-users.show', compact('user'));
    }

    /**
     * Impersonate user
     */
    public function impersonate(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Store original user ID
        session(['impersonate_from' => auth()->id()]);
        
        // Login as target user
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('info', "Anda sekarang impersonate sebagai {$user->name}");
    }

    /**
     * Stop impersonating
     */
    public function stopImpersonating()
    {
        if (!session()->has('impersonate_from')) {
            return redirect()->route('dashboard');
        }

        $originalUserId = session('impersonate_from');
        $originalUser = User::findOrFail($originalUserId);

        auth()->login($originalUser);
        session()->forget('impersonate_from');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonation dihentikan.');
    }

    /**
     * Display company users (for company admin/owner)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Company admin can only see their company users
        abort_unless($user->hasAdminAccess(), 403);

        $query = User::where('company_id', $user->company_id)
            ->where('id', '!=', $user->id); // Exclude self

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            }
        }

        $users = $query->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $user = auth()->user();
        abort_unless($user->hasAdminAccess(), 403);

        return view('users.create');
    }

    /**
     * Store a newly created user in company
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->hasAdminAccess(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,manager,staff,user',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'company_id' => $currentUser->company_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $currentUser = auth()->user();

        // Ensure same company
        abort_unless($user->company_id === $currentUser->company_id, 403);

        $user->load([
            'bankStatements' => fn($q) => $q->latest()->limit(5),
            'verifiedTransactions' => fn($q) => $q->latest()->limit(5),
        ]);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasAdminAccess(), 403);
        abort_unless($user->company_id === $currentUser->company_id, 403);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasAdminAccess(), 403);
        abort_unless($user->company_id === $currentUser->company_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:admin,manager,staff,user',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User berhasil diupdate.');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasAdminAccess(), 403);
        abort_unless($user->company_id === $currentUser->company_id, 403);

        // Prevent deleting self
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Prevent deleting owner
        if ($user->isOwner()) {
            return back()->with('error', 'Tidak dapat menghapus owner company.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$userName}' berhasil dihapus.");
    }

    /**
     * Toggle user role
     */
    public function toggleRole(Request $request, User $user)
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasAdminAccess(), 403);
        abort_unless($user->company_id === $currentUser->company_id, 403);

        $validated = $request->validate([
            'role' => 'required|in:admin,manager,staff,user',
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Role user berhasil diubah ke '{$validated['role']}'.");
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasAdminAccess(), 403);
        abort_unless($user->company_id === $currentUser->company_id, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'require_password_change' => true,
        ]);

        return back()->with('success', 'Password user berhasil direset.');
    }
}