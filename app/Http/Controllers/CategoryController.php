<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Category::where('company_id', $user->company_id)
            ->with('type')
            ->withCount(['subCategories', 'transactions']);

        // Filter by type
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('sort_order')->paginate(20);

        // Get types for filter
        $types = Type::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return view('categories.index', compact('categories', 'types'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $types = Type::where('company_id', auth()->user()->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return view('categories.create', compact('types'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_id' => 'required|exists:types,id',
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Verify type belongs to user's company
        $type = Type::where('id', $validated['type_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $category = Category::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'type_id' => $type->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#3B82F6',
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('categories.index')
            ->with('success', "Category '{$category->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        abort_unless($category->company_id === auth()->user()->company_id, 403);

        $category->load(['type', 'subCategories' => function($q) {
            $q->withCount(['keywords', 'transactions'])->orderBy('sort_order');
        }]);

        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(Category $category)
    {
        abort_unless($category->company_id === auth()->user()->company_id, 403);

        $types = Type::where('company_id', auth()->user()->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return view('categories.edit', compact('category', 'types'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category)
    {
        abort_unless($category->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'type_id' => 'required|exists:types,id',
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Verify type belongs to user's company
        $type = Type::where('id', $validated['type_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $category->update([
            'type_id' => $type->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $category->slug,
            'description' => $validated['description'],
            'color' => $validated['color'],
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()->route('categories.show', $category)
            ->with('success', 'Category berhasil diupdate.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        abort_unless($category->company_id === auth()->user()->company_id, 403);

        // Check if category has sub categories
        if ($category->subCategories()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus category yang memiliki sub categories.');
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', "Category '{$categoryName}' berhasil dihapus.");
    }

    /**
     * Get categories by type (AJAX)
     */
    public function getByType(Request $request, $typeId)
    {
        $categories = Category::where('company_id', auth()->user()->company_id)
            ->where('type_id', $typeId)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'color']);

        return response()->json($categories);
    }

    /**
     * Reorder categories (AJAX)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:categories,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            Category::where('id', $order['id'])
                ->where('company_id', auth()->user()->company_id)
                ->update(['sort_order' => $order['sort_order']]);
        }

        return response()->json(['message' => 'Order berhasil diupdate.']);
    }

    /**
     * API: Get categories by type (for dynamic dropdowns)
     */
    public function apiGetByType(Request $request, $typeId)
    {
        $categories = Category::where('company_id', auth()->user()->company_id)
            ->where('type_id', $typeId)
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'color']);

        return response()->json($categories);
    }
}


// ============================================================================
// FILE: app/Http/Controllers/SubCategoryController.php
// ============================================================================

namespace App\Http\Controllers;

use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of sub categories (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = SubCategory::where('company_id', $user->company_id)
            ->with('category.type')
            ->withCount(['keywords', 'transactions']);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by type
        if ($request->filled('type_id')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('type_id', $request->type_id);
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $subCategories = $query->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->paginate(20);

        // Get types and categories for filters
        $types = Type::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $categories = Category::where('company_id', $user->company_id)
            ->with('type')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type_id']);

        return view('sub-categories.index', compact('subCategories', 'types', 'categories'));
    }

    /**
     * Show the form for creating a new sub category
     */
    public function create()
    {
        $types = Type::where('company_id', auth()->user()->company_id)
            ->with(['categories' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return view('sub-categories.create', compact('types'));
    }

    /**
     * Store a newly created sub category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Verify category belongs to user's company
        $category = Category::where('id', $validated['category_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $subCategory = SubCategory::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'category_id' => $category->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('sub-categories.index')
            ->with('success', "Sub Category '{$subCategory->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified sub category
     */
    public function show(SubCategory $subCategory)
    {
        abort_unless($subCategory->company_id === auth()->user()->company_id, 403);

        $subCategory->load([
            'category.type',
            'keywords' => fn($q) => $q->orderBy('priority', 'desc'),
        ]);

        // Get recent transactions
        $recentTransactions = $subCategory->transactions()
            ->with('bankStatement.bank')
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('sub-categories.show', compact('subCategory', 'recentTransactions'));
    }

    /**
     * Show the form for editing the specified sub category
     */
    public function edit(SubCategory $subCategory)
    {
        abort_unless($subCategory->company_id === auth()->user()->company_id, 403);

        $types = Type::where('company_id', auth()->user()->company_id)
            ->with(['categories' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return view('sub-categories.edit', compact('subCategory', 'types'));
    }

    /**
     * Update the specified sub category
     */
    public function update(Request $request, SubCategory $subCategory)
    {
        abort_unless($subCategory->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Verify category belongs to user's company
        $category = Category::where('id', $validated['category_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $subCategory->update([
            'category_id' => $category->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'sort_order' => $validated['sort_order'],
        ]);

        return redirect()->route('sub-categories.show', $subCategory)
            ->with('success', 'Sub Category berhasil diupdate.');
    }

    /**
     * Remove the specified sub category
     */
    public function destroy(SubCategory $subCategory)
    {
        abort_unless($subCategory->company_id === auth()->user()->company_id, 403);

        // Check if sub category has keywords
        if ($subCategory->keywords()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus sub category yang memiliki keywords. Hapus keywords terlebih dahulu.');
        }

        $subCategoryName = $subCategory->name;
        $subCategory->delete();

        return redirect()->route('sub-categories.index')
            ->with('success', "Sub Category '{$subCategoryName}' berhasil dihapus.");
    }

    /**
     * Get sub categories by category (AJAX)
     */
    public function getByCategory(Request $request, $categoryId)
    {
        $subCategories = SubCategory::where('company_id', auth()->user()->company_id)
            ->where('category_id', $categoryId)
            ->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'priority']);

        return response()->json($subCategories);
    }

    /**
     * Reorder sub categories (AJAX)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:sub_categories,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            SubCategory::where('id', $order['id'])
                ->where('company_id', auth()->user()->company_id)
                ->update(['sort_order' => $order['sort_order']]);
        }

        return response()->json(['message' => 'Order berhasil diupdate.']);
    }

    /**
     * Bulk update priority (AJAX)
     */
    public function bulkUpdatePriority(Request $request)
    {
        $validated = $request->validate([
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|exists:sub_categories,id',
            'priorities.*.priority' => 'required|integer|min:1|max:10',
        ]);

        foreach ($validated['priorities'] as $item) {
            SubCategory::where('id', $item['id'])
                ->where('company_id', auth()->user()->company_id)
                ->update(['priority' => $item['priority']]);
        }

        return response()->json(['message' => 'Priorities berhasil diupdate.']);
    }

    /**
     * API: Get sub categories by category (for dynamic dropdowns)
     */
    public function apiGetByCategory(Request $request, $categoryId)
    {
        $subCategories = SubCategory::where('company_id', auth()->user()->company_id)
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'priority']);

        return response()->json($subCategories);
    }
}