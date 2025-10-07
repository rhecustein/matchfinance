<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the sub categories
     */
    public function index(Request $request)
    {
        $query = SubCategory::with('category.type')
            ->withCount('keywords');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subCategories = $query->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $categories = Category::with('type')->orderBy('name')->get();

        return view('sub-categories.index', compact('subCategories', 'categories'));
    }

    /**
     * Show the form for creating a new sub category
     */
    public function create()
    {
        $categories = Category::with('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type.name');

        return view('sub-categories.create', compact('categories'));
    }

    /**
     * Store a newly created sub category
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            SubCategory::create($request->all());

            return redirect()
                ->route('sub-categories.index')
                ->with('success', 'Sub Category created successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to create sub category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified sub category
     */
    public function show(SubCategory $subCategory)
    {
        $subCategory->load([
            'category.type',
            'keywords' => function($query) {
                $query->orderBy('priority', 'desc');
            }
        ]);

        $stats = [
            'total_keywords' => $subCategory->keywords()->count(),
            'active_keywords' => $subCategory->keywords()->active()->count(),
            'total_transactions' => $subCategory->transactions()->count(),
            'verified_transactions' => $subCategory->transactions()->verified()->count(),
        ];

        return view('sub-categories.show', compact('subCategory', 'stats'));
    }

    /**
     * Show the form for editing the sub category
     */
    public function edit(SubCategory $subCategory)
    {
        $categories = Category::with('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type.name');

        return view('sub-categories.edit', compact('subCategory', 'categories'));
    }

    /**
     * Update the specified sub category
     */
    public function update(Request $request, SubCategory $subCategory)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $subCategory->update($request->all());

            return redirect()
                ->route('sub-categories.index')
                ->with('success', 'Sub Category updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to update sub category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified sub category
     */
    public function destroy(SubCategory $subCategory)
    {
        try {
            // Check if sub category has keywords
            if ($subCategory->keywords()->exists()) {
                return back()->with('error', 'Cannot delete sub category with existing keywords.');
            }

            $subCategory->delete();

            return redirect()
                ->route('sub-categories.index')
                ->with('success', 'Sub Category deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete sub category: ' . $e->getMessage());
        }
    }

    /**
     * Get sub categories by category (AJAX)
     */
    public function getByCategory($categoryId)
    {
        try {
            $subCategories = SubCategory::where('category_id', $categoryId)
                ->orderBy('priority', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'priority']);

            return response()->json([
                'success' => true,
                'data' => $subCategories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder sub categories
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'sub_categories' => 'required|array',
            'sub_categories.*.id' => 'required|exists:sub_categories,id',
            'sub_categories.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($request->sub_categories as $subCategoryData) {
                SubCategory::where('id', $subCategoryData['id'])
                    ->update(['sort_order' => $subCategoryData['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sub Categories reordered successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder sub categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update priority
     */
    public function bulkUpdatePriority(Request $request)
    {
        $request->validate([
            'sub_category_ids' => 'required|array',
            'sub_category_ids.*' => 'exists:sub_categories,id',
            'priority' => 'required|integer|min:1|max:10',
        ]);

        try {
            SubCategory::whereIn('id', $request->sub_category_ids)
                ->update(['priority' => $request->priority]);

            return back()->with('success', count($request->sub_category_ids) . ' sub categories priority updated.');

        } catch (\Exception $e) {
            return back()->with('error', 'Bulk update failed: ' . $e->getMessage());
        }
    }
}