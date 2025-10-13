<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BankController extends Controller
{
    /**
     * Constructor - Only Super Admin can manage banks
     */
    public function __construct()
    {
        // Hanya Super Admin yang bisa akses management banks
        // Karena banks adalah master data global
        $this->middleware('super_admin');
    }

    /**
     * Display a listing of banks (Global - No Company Scope)
     */
    public function index(Request $request)
    {
        // Build query without company scope
        $query = Bank::withCount('bankStatements');

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
            'total' => Bank::count(),
            'active' => Bank::where('is_active', true)->count(),
            'inactive' => Bank::where('is_active', false)->count(),
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
        // Load relationships
        $bank->loadCount(['bankStatements', 'transactions']);

        // Recent statements across all companies
        $recentStatements = $bank->bankStatements()
            ->with(['user', 'company'])
            ->latest()
            ->limit(10)
            ->get();

        // Statistics per company (if needed)
        $companyStats = $bank->bankStatements()
            ->selectRaw('company_id, COUNT(*) as statement_count')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get();

        return view('banks.show', compact('bank', 'recentStatements', 'companyStats'));
    }

    /**
     * Show the form for editing the specified bank
     */
    public function edit(Bank $bank)
    {
        return view('banks.edit', compact('bank'));
    }

    /**
     * Update the specified bank
     */
    public function update(Request $request, Bank $bank)
    {
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
     * Remove the specified bank (Soft Delete)
     */
    public function destroy(Bank $bank)
    {
        // Check if bank has statements across all companies
        $statementsCount = $bank->bankStatements()->count();
        
        if ($statementsCount > 0) {
            return back()->with('error', "Tidak dapat menghapus bank yang memiliki {$statementsCount} bank statements. Nonaktifkan saja jika diperlukan.");
        }

        $bankName = $bank->name;
        $bank->delete(); // Soft delete

        return redirect()->route('banks.index')
            ->with('success', "Bank '{$bankName}' berhasil dihapus.");
    }

    /**
     * Toggle bank active status
     */
    public function toggleActive(Bank $bank)
    {
        $bank->update(['is_active' => !$bank->is_active]);

        $status = $bank->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Bank '{$bank->name}' berhasil {$status}.");
    }

    /**
     * Restore soft deleted bank
     */
    public function restore($id)
    {
        $bank = Bank::withTrashed()->findOrFail($id);
        $bank->restore();

        return back()->with('success', "Bank '{$bank->name}' berhasil direstore.");
    }

    /**
     * View trashed banks
     */
    public function trashed(Request $request)
    {
        $query = Bank::onlyTrashed()->withCount('bankStatements');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $banks = $query->orderBy('deleted_at', 'desc')->paginate(20);

        return view('banks.trashed', compact('banks'));
    }

    /**
     * Force delete permanently
     */
    public function forceDelete($id)
    {
        $bank = Bank::withTrashed()->findOrFail($id);
        
        // Check statements one more time
        if ($bank->bankStatements()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus permanent bank yang memiliki statements.');
        }

        $bankName = $bank->name;
        
        // Delete logo if exists
        if ($bank->logo) {
            Storage::delete(str_replace('storage/', 'public/', $bank->logo));
        }
        
        $bank->forceDelete();

        return back()->with('success', "Bank '{$bankName}' berhasil dihapus permanent.");
    }
}