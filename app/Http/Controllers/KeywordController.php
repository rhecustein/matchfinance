<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KeywordController extends Controller
{
    /**
     * Display a listing of keywords (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Keyword::where('company_id', $user->company_id)
            ->with('subCategory.category.type');

        // Filter by sub category
        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('subCategory', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by type
        if ($request->filled('type_id')) {
            $query->whereHas('subCategory.category', function($q) use ($request) {
                $q->where('type_id', $request->type_id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by match type
        if ($request->filled('match_type')) {
            if ($request->match_type === 'regex') {
                $query->where('is_regex', true);
            } elseif ($request->match_type === 'exact') {
                $query->where('is_regex', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('keyword', 'like', "%{$search}%")
                  ->orWhere('pattern_description', 'like', "%{$search}%");
            });
        }

        $keywords = $query->orderBy('priority', 'desc')
            ->orderBy('match_count', 'desc')
            ->paginate(30);

        // Get filters data
        $types = Type::where('company_id', $user->company_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $categories = Category::where('company_id', $user->company_id)
            ->with('type')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type_id']);

        $subCategories = SubCategory::where('company_id', $user->company_id)
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id']);

        return view('keywords.index', compact('keywords', 'types', 'categories', 'subCategories'));
    }

    /**
     * Show the form for creating a new keyword
     */
    public function create()
    {
        $types = Type::where('company_id', auth()->user()->company_id)
            ->with(['categories.subCategories'])
            ->orderBy('sort_order')
            ->get();

        return view('keywords.create', compact('types'));
    }

    /**
     * Store a newly created keyword
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'match_type' => 'required|in:contains,exact,starts_with,ends_with,regex',
            'pattern_description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        // Verify sub category belongs to user's company
        $subCategory = SubCategory::where('id', $validated['sub_category_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        // Validate regex if is_regex is true
        if ($validated['is_regex'] ?? false) {
            if (@preg_match('/' . $validated['keyword'] . '/u', '') === false) {
                return back()->withInput()
                    ->with('error', 'Pattern regex tidak valid. Silakan periksa kembali.');
            }
        }

        $keyword = Keyword::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'sub_category_id' => $subCategory->id,
            'keyword' => $validated['keyword'],
            'is_regex' => $validated['is_regex'] ?? false,
            'case_sensitive' => $validated['case_sensitive'] ?? false,
            'match_type' => $validated['match_type'],
            'pattern_description' => $validated['pattern_description'] ?? null,
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('keywords.index')
            ->with('success', "Keyword '{$keyword->keyword}' berhasil ditambahkan.");
    }

    /**
     * Display the specified keyword
     */
    public function show(Keyword $keyword)
    {
        abort_unless($keyword->company_id === auth()->user()->company_id, 403);

        $keyword->load('subCategory.category.type');

        // Get recent matched transactions
        $recentMatches = $keyword->transactions()
            ->with('bankStatement.bank')
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('keywords.show', compact('keyword', 'recentMatches'));
    }

    /**
     * Show the form for editing the specified keyword
     */
    public function edit(Keyword $keyword)
    {
        abort_unless($keyword->company_id === auth()->user()->company_id, 403);

        $types = Type::where('company_id', auth()->user()->company_id)
            ->with(['categories.subCategories'])
            ->orderBy('sort_order')
            ->get();

        return view('keywords.edit', compact('keyword', 'types'));
    }

    /**
     * Update the specified keyword
     */
    public function update(Request $request, Keyword $keyword)
    {
        abort_unless($keyword->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'keyword' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'match_type' => 'required|in:contains,exact,starts_with,ends_with,regex',
            'pattern_description' => 'nullable|string',
            'priority' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        // Verify sub category belongs to user's company
        $subCategory = SubCategory::where('id', $validated['sub_category_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        // Validate regex if is_regex is true
        if ($validated['is_regex'] ?? false) {
            if (@preg_match('/' . $validated['keyword'] . '/u', '') === false) {
                return back()->withInput()
                    ->with('error', 'Pattern regex tidak valid. Silakan periksa kembali.');
            }
        }

        $keyword->update([
            'sub_category_id' => $subCategory->id,
            'keyword' => $validated['keyword'],
            'is_regex' => $validated['is_regex'] ?? $keyword->is_regex,
            'case_sensitive' => $validated['case_sensitive'] ?? $keyword->case_sensitive,
            'match_type' => $validated['match_type'],
            'pattern_description' => $validated['pattern_description'],
            'priority' => $validated['priority'],
            'is_active' => $validated['is_active'] ?? $keyword->is_active,
        ]);

        return redirect()->route('keywords.show', $keyword)
            ->with('success', 'Keyword berhasil diupdate.');
    }

    /**
     * Remove the specified keyword
     */
    public function destroy(Keyword $keyword)
    {
        abort_unless($keyword->company_id === auth()->user()->company_id, 403);

        $keywordText = $keyword->keyword;
        $keyword->delete();

        return redirect()->route('keywords.index')
            ->with('success', "Keyword '{$keywordText}' berhasil dihapus.");
    }

    /**
     * Toggle keyword active status
     */
    public function toggleActive(Keyword $keyword)
    {
        abort_unless($keyword->company_id === auth()->user()->company_id, 403);

        $keyword->update(['is_active' => !$keyword->is_active]);

        $status = $keyword->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Keyword '{$keyword->keyword}' berhasil {$status}.");
    }

    /**
     * Bulk update priority (AJAX)
     */
    public function bulkUpdatePriority(Request $request)
    {
        $validated = $request->validate([
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|exists:keywords,id',
            'priorities.*.priority' => 'required|integer|min:1|max:10',
        ]);

        foreach ($validated['priorities'] as $item) {
            Keyword::where('id', $item['id'])
                ->where('company_id', auth()->user()->company_id)
                ->update(['priority' => $item['priority']]);
        }

        return response()->json(['message' => 'Priorities berhasil diupdate.']);
    }

    /**
     * Test keyword matching (AJAX)
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
            'test_string' => 'required|string',
            'is_regex' => 'boolean',
            'case_sensitive' => 'boolean',
            'match_type' => 'required|in:contains,exact,starts_with,ends_with,regex',
        ]);

        $keyword = $validated['keyword'];
        $testString = $validated['test_string'];
        $isRegex = $validated['is_regex'] ?? false;
        $caseSensitive = $validated['case_sensitive'] ?? false;
        $matchType = $validated['match_type'];

        $matched = false;
        $matchedText = '';
        $error = null;

        try {
            if (!$caseSensitive) {
                $keyword = strtolower($keyword);
                $testString = strtolower($testString);
            }

            if ($isRegex || $matchType === 'regex') {
                // Test regex
                $result = @preg_match('/' . $keyword . '/u', $testString, $matches);
                
                if ($result === false) {
                    $error = 'Regex pattern tidak valid';
                } else {
                    $matched = $result === 1;
                    $matchedText = $matches[0] ?? '';
                }
            } else {
                // Test string matching
                $matched = match($matchType) {
                    'exact' => $testString === $keyword,
                    'contains' => str_contains($testString, $keyword),
                    'starts_with' => str_starts_with($testString, $keyword),
                    'ends_with' => str_ends_with($testString, $keyword),
                    default => false,
                };

                if ($matched) {
                    $matchedText = $keyword;
                }
            }

        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return response()->json([
            'matched' => $matched,
            'matched_text' => $matchedText,
            'error' => $error,
        ]);
    }
}