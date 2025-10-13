<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeController extends Controller
{
    /**
     * Display a listing of types (Company Scoped)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Type::where('company_id', $user->company_id)
            ->withCount(['categories', 'transactions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $types = $query->orderBy('sort_order')->paginate(20);

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
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $type = Type::create([
            'uuid' => Str::uuid(),
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('types.index')
            ->with('success', "Type '{$type->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified type
     */
    public function show(Type $type)
    {
        abort_unless($type->company_id === auth()->user()->company_id, 403);

        $type->load(['categories' => function($q) {
            $q->withCount(['subCategories', 'transactions']);
        }]);

        return view('types.show', compact('type'));
    }

    /**
     * Show the form for editing the specified type
     */
    public function edit(Type $type)
    {
        abort_unless($type->company_id === auth()->user()->company_id, 403);

        return view('types.edit', compact('type'));
    }

    /**
     * Update the specified type
     */
    public function update(Request $request, Type $type)
    {
        abort_unless($type->company_id === auth()->user()->company_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $type->update($validated);

        return redirect()->route('types.show', $type)
            ->with('success', 'Type berhasil diupdate.');
    }

    /**
     * Remove the specified type
     */
    public function destroy(Type $type)
    {
        abort_unless($type->company_id === auth()->user()->company_id, 403);

        // Check if type has categories
        if ($type->categories()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus type yang memiliki categories.');
        }

        $typeName = $type->name;
        $type->delete();

        return redirect()->route('types.index')
            ->with('success', "Type '{$typeName}' berhasil dihapus.");
    }

    /**
     * Reorder types (AJAX)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:types,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            Type::where('id', $order['id'])
                ->where('company_id', auth()->user()->company_id)
                ->update(['sort_order' => $order['sort_order']]);
        }

        return response()->json(['message' => 'Order berhasil diupdate.']);
    }
}
