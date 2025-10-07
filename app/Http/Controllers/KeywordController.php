<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KeywordController extends Controller
{
    /**
     * Display keywords list with eager loading
     */
    public function index()
    {
        $keywords = Keyword::with('subCategory.category.type')
            ->latest()
            ->paginate(20);

        return view('keywords.index', compact('keywords'));
    }

    /**
     * Show the form for creating a new keyword
     */
    public function create(Request $request)
    {
        $subCategories = SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        // Pre-select sub category if coming from sub category detail page
        $selectedSubCategoryId = $request->get('sub_category_id');

        return view('keywords.create', compact('subCategories', 'selectedSubCategoryId'));
    }

    /**
     * Store a newly created keyword
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:10',
            'is_regex' => 'nullable|boolean',
            'case_sensitive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // Set default values for checkboxes if not present
        $validated['is_regex'] = $request->has('is_regex') ? true : false;
        $validated['case_sensitive'] = $request->has('case_sensitive') ? true : false;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        try {
            Keyword::create($validated);

            // Clear matching cache when keywords are modified
            $this->clearMatchingCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword created successfully. Matching cache cleared.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to create keyword: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the keyword
     */
    public function edit(Keyword $keyword)
    {
        // Load relationships
        $keyword->load('subCategory.category.type');

        $subCategories = SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        return view('keywords.edit', compact('keyword', 'subCategories'));
    }

    /**
     * Update the keyword
     */
    public function update(Request $request, Keyword $keyword)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:10',
            'is_regex' => 'nullable|boolean',
            'case_sensitive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // Set default values for checkboxes if not present
        $validated['is_regex'] = $request->has('is_regex') ? true : false;
        $validated['case_sensitive'] = $request->has('case_sensitive') ? true : false;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        try {
            $keyword->update($validated);

            // Clear matching cache
            $this->clearMatchingCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword updated successfully. Matching cache cleared.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to update keyword: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the keyword
     */
    public function destroy(Keyword $keyword)
    {
        try {
            $keyword->delete();

            // Clear matching cache
            $this->clearMatchingCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword deleted successfully. Matching cache cleared.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete keyword: ' . $e->getMessage());
        }
    }

    /**
     * Clear the matching cache
     * This is crucial for ensuring updated keywords are used in matching
     */
    private function clearMatchingCache(): void
    {
        Cache::forget('active_keywords');
        
        // You can add more cache keys here if needed
        // For example, if you cache keywords by sub_category_id
        // Cache::tags(['keywords'])->flush();
    }
}