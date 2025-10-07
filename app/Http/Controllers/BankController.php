<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankController extends Controller
{
    /**
     * Display a listing of the banks
     */
    public function index()
    {
        $banks = Bank::withCount('bankStatements')
            ->orderBy('name')
            ->paginate(15);

        return view('banks.index', compact('banks'));
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
        $request->validate([
            'code' => 'required|string|max:10|unique:banks,code',
            'name' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        try {
            $data = $request->only(['code', 'name', 'is_active']);
            
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('banks/logos', 'public');
                $data['logo'] = $logoPath;
            }

            Bank::create($data);

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank created successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to create bank: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified bank
     */
    public function show(Bank $bank)
    {
        $bank->load(['bankStatements' => function($query) {
            $query->latest('uploaded_at')->limit(10);
        }]);

        $stats = [
            'total_statements' => $bank->bankStatements()->count(),
            'pending' => $bank->bankStatements()->status('pending')->count(),
            'processing' => $bank->bankStatements()->status('processing')->count(),
            'completed' => $bank->bankStatements()->status('completed')->count(),
            'failed' => $bank->bankStatements()->status('failed')->count(),
        ];

        return view('banks.show', compact('bank', 'stats'));
    }

    /**
     * Show the form for editing the bank
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
        $request->validate([
            'code' => 'required|string|max:10|unique:banks,code,' . $bank->id,
            'name' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        try {
            $data = $request->only(['code', 'name', 'is_active']);
            
            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo
                if ($bank->logo) {
                    Storage::disk('public')->delete($bank->logo);
                }
                
                $logoPath = $request->file('logo')->store('banks/logos', 'public');
                $data['logo'] = $logoPath;
            }

            $bank->update($data);

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to update bank: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified bank
     */
    public function destroy(Bank $bank)
    {
        try {
            // Check if bank has statements
            if ($bank->bankStatements()->exists()) {
                return back()->with('error', 'Cannot delete bank with existing statements.');
            }

            // Delete logo if exists
            if ($bank->logo) {
                Storage::disk('public')->delete($bank->logo);
            }

            $bank->delete();

            return redirect()
                ->route('banks.index')
                ->with('success', 'Bank deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete bank: ' . $e->getMessage());
        }
    }

    /**
     * Toggle bank active status
     */
    public function toggleActive(Bank $bank)
    {
        try {
            $bank->update(['is_active' => !$bank->is_active]);

            $status = $bank->is_active ? 'activated' : 'deactivated';

            return back()->with('success', "Bank {$status} successfully.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle bank status: ' . $e->getMessage());
        }
    }
}