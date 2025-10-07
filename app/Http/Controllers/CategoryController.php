<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories
     */
    public function index(Request $request)
    {
        $query = Category::with('type')
            ->withCount('subCategories');

        // Filter by type
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        $categories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $types = Type::orderBy('name')->get();

        return view('categories.index', compact('categories', 'types'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create(Request $request)
    {
        $types = Type::orderBy('name')->get();
        
        // Pre-select type if coming from type detail page
        $selectedTypeId = $request->get('type_id');
        
        return view('categories.create', compact('types', 'selectedTypeId'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:types,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $data = $request->all();
            
            // Set default color if not provided
            if (!isset($data['color'])) {
                $data['color'] = '#3B82F6'; // Default blue
            }

            Category::create($data);

            return redirect()
                ->route('categories.index')
                ->with('success', 'Category created successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to create category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified category with detailed statistics
     * 
     * OPTIMIZED:
     * - Using loadCount for efficient queries
     * - Loading relationships properly
     * - Avoiding N+1 query problems
     */
    public function show(Category $category)
    {
        // Eager load relationships and counts efficiently
        $category->load('type');
        
        $category->loadCount([
            'subCategories',
            'transactions',
            'transactions as verified_transactions_count' => function ($query) {
                $query->where('is_verified', true);
            }
        ]);

        // Load subcategories with their keywords count
        $category->load(['subCategories' => function($query) {
            $query->withCount('keywords')
                  ->orderBy('priority', 'desc')
                  ->orderBy('sort_order')
                  ->orderBy('name');
        }]);

        // Calculate total keywords from all subcategories
        $totalKeywords = $category->subCategories->sum('keywords_count');

        // Prepare statistics using loaded counts
        $stats = [
            'total_subcategories' => $category->sub_categories_count,
            'total_keywords' => $totalKeywords,
            'total_transactions' => $category->transactions_count,
            'verified_transactions' => $category->verified_transactions_count,
        ];

        return view('categories.show', compact('category', 'stats'));
    }

    /**
     * Show the form for editing the category
     */
    public function edit(Category $category)
    {
        $types = Type::orderBy('name')->get();
        
        return view('categories.edit', compact('category', 'types'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'type_id' => 'required|exists:types,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $category->update($request->all());

            return redirect()
                ->route('categories.index')
                ->with('success', 'Category updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to update category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        try {
            // Check if category has sub categories
            if ($category->subCategories()->exists()) {
                return back()->with('error', 'Cannot delete category with existing sub categories.');
            }

            $category->delete();

            return redirect()
                ->route('categories.index')
                ->with('success', 'Category deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Get categories by type (AJAX)
     * Used for dynamic dropdowns in forms
     */
    public function getByType($typeId)
    {
        try {
            $categories = Category::where('type_id', $typeId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color']);

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder categories via drag and drop
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($request->categories as $categoryData) {
                Category::where('id', $categoryData['id'])
                    ->update(['sort_order' => $categoryData['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage()
            ], 500);
        }
    }
}