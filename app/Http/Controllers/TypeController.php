<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    /**
     * Display a listing of the types
     */
    public function index()
    {
        $types = Type::withCount('categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('types.index', compact('types'));
    }

    /**
     * Show the form for creating a new type
     */
    public function create()
    {
        return view('types.create');
    }

    /**
     * Store a newly created type
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:types,name',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            Type::create($request->all());

            return redirect()
                ->route('types.index')
                ->with('success', 'Type created successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to create type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified type with detailed statistics
     * 
     * PERBAIKAN:
     * - Menggunakan loadCount untuk efisiensi query
     * - Menghitung verified transactions dengan conditional count
     * - Load categories dengan relasi yang dibutuhkan
     */
    public function show(Type $type)
    {
        // Eager load counts untuk menghindari N+1 query
        $type->loadCount([
            'categories',
            'transactions',
            'transactions as verified_transactions_count' => function ($query) {
                $query->where('is_verified', true);
            }
        ]);

        // Load categories dengan subcategories count untuk display
        $type->load(['categories' => function($query) {
            $query->withCount('subCategories')
                  ->orderBy('sort_order')
                  ->orderBy('name');
        }]);

        // Prepare statistics dari loaded counts
        // Ini lebih efisien karena tidak melakukan query lagi
        $stats = [
            'total_categories' => $type->categories_count,
            'total_transactions' => $type->transactions_count,
            'verified_transactions' => $type->verified_transactions_count,
        ];

        return view('types.show', compact('type', 'stats'));
    }

    /**
     * Show the form for editing the type
     */
    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    /**
     * Update the specified type
     */
    public function update(Request $request, Type $type)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:types,name,' . $type->id,
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $type->update($request->all());

            return redirect()
                ->route('types.index')
                ->with('success', 'Type updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to update type: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified type
     */
    public function destroy(Type $type)
    {
        try {
            // Check if type has categories
            if ($type->categories()->exists()) {
                return back()->with('error', 'Cannot delete type with existing categories.');
            }

            $type->delete();

            return redirect()
                ->route('types.index')
                ->with('success', 'Type deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete type: ' . $e->getMessage());
        }
    }

    /**
     * Reorder types via drag and drop
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'types' => 'required|array',
            'types.*.id' => 'required|exists:types,id',
            'types.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($request->types as $typeData) {
                Type::where('id', $typeData['id'])
                    ->update(['sort_order' => $typeData['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Types reordered successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder types: ' . $e->getMessage()
            ], 500);
        }
    }
}