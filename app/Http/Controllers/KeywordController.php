<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\SubCategory;
use App\Services\TransactionMatchingService;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    /**
     * Display keywords list
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
    public function create()
    {
        $subCategories = SubCategory::with('category.type')
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        return view('keywords.create', compact('subCategories'));
    }

    /**
     * Store a newly created keyword
     */
    public function store(Request $request)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:10',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            Keyword::create($request->all());

            // Clear cache
            TransactionMatchingService::clearCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword created successfully.');

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
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'priority' => 'required|integer|min:1|max:10',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $keyword->update($request->all());

            // Clear cache
            TransactionMatchingService::clearCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword updated successfully.');

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

            // Clear cache
            TransactionMatchingService::clearCache();

            return redirect()
                ->route('keywords.index')
                ->with('success', 'Keyword deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete keyword: ' . $e->getMessage());
        }
    }
}