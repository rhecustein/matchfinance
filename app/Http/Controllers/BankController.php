<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BankController extends Controller
{
    /**
     * Display a listing of banks (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Build query with company scope
        $query = Bank::where('company_id', $user->company_id)
            ->withCount('bankStatements');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $banks = $query->orderBy('name')->paginate(20);

        $stats = [
            'total' => Bank::where('company_id', $user->company_id)->count(),
            'active' => Bank::where('company_id', $user->company_id)->where('is_active', true)->count(),
            'inactive' => Bank::where('company_id', $user->company_id)->where('is_active', false)->count(),
        ];

        return view('banks.index', compact('banks', 'stats'));
    }

    /**
     * Show the form for creating a new bank
     */
    public function create()
    {
        return view('banks.create');
    }

    /**
     * Store a newly created bank
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:banks,code',
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:banks,slug',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        $bank = Bank::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'code' => strtoupper($validated['code']),
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/logos/banks');
            $bank->update(['logo' => str_replace('public/', 'storage/', $path)]);
        }

        return redirect()->route('banks.index')
            ->with('success', "Bank '{$bank->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified bank
     */
    public function show(Bank $bank)
    {
        // Ensure same company
        abort_unless($bank->company_id === auth()->user()->company_id, 403);

        $bank->loadCount(['bankStatements', 'transactions']);

        // Recent statements
        $recentStatements = $bank->bankStatements()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('banks.show', compact('bank', 'recentStatements'));
    }

    /**
     * Show the form for editing the specified bank
     */
    public function edit(Bank $bank)
    {
        abort_unless($bank->company_id === auth()->user()->company_id, 403);

        return view('banks.edit', compact('bank'));
    }

    /**
     * Update the specified bank
     */
    public function update(Request $request, Bank $bank)
    {
        abort_unless($bank->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:banks,code,' . $bank->id,
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:banks,slug,' . $bank->id,
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        $bank->update([
            'code' => strtoupper($validated['code']),
            'slug' => $validated['slug'] ?? $bank->slug,
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $bank->is_active,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($bank->logo) {
                Storage::delete(str_replace('storage/', 'public/', $bank->logo));
            }

            $path = $request->file('logo')->store('public/logos/banks');
            $bank->update(['logo' => str_replace('public/', 'storage/', $path)]);
        }

        return redirect()->route('banks.show', $bank)
            ->with('success', 'Bank berhasil diupdate.');
    }

    /**
     * Remove the specified bank
     */
    public function destroy(Bank $bank)
    {
        abort_unless($bank->company_id === auth()->user()->company_id, 403);

        // Check if bank has statements
        if ($bank->bankStatements()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus bank yang memiliki bank statements.');
        }

        $bankName = $bank->name;
        $bank->delete();

        return redirect()->route('banks.index')
            ->with('success', "Bank '{$bankName}' berhasil dihapus.");
    }

    /**
     * Toggle bank active status
     */
    public function toggleActive(Bank $bank)
    {
        abort_unless($bank->company_id === auth()->user()->company_id, 403);

        $bank->update(['is_active' => !$bank->is_active]);

        $status = $bank->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Bank '{$bank->name}' berhasil {$status}.");
    }
}