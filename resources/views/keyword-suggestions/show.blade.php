{{-- Add this section after the header in bank-statements/show.blade.php --}}

{{-- Action Buttons --}}
<div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl mb-6">
    <div class="flex flex-wrap items-center gap-3">
        {{-- Suggest Keywords (NEW) --}}
        @if($matchingStats['unmatched_count'] > 0)
            <a href="{{ route('keyword-suggestions.analyze', $bankStatement) }}" 
               class="bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                <i class="fas fa-lightbulb"></i>
                <span>Suggest Keywords</span>
                <span class="px-2 py-1 bg-white/20 rounded text-xs">
                    {{ $matchingStats['unmatched_count'] }} unmatched
                </span>
            </a>
        @endif

        {{-- Process Matching --}}
        @if($bankStatement->transactions()->count() > 0)
            <form action="{{ route('bank-statements.process-matching', $bankStatement) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                    <i class="fas fa-sync-alt"></i>
                    <span>Process Matching</span>
                </button>
            </form>

            {{-- Rematch All --}}
            <form action="{{ route('bank-statements.rematch-all', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('This will reset all unverified matches. Continue?')">
                @csrf
                <button type="submit" class="bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                    <i class="fas fa-redo"></i>
                    <span>Rematch All</span>
                </button>
            </form>

            {{-- Verify All Matched --}}
            @if($matchingStats['matched_count'] > 0)
                <form action="{{ route('bank-statements.verify-all-matched', $bankStatement) }}" method="POST" class="inline" onsubmit="return confirm('Verify all matched transactions?')">
                    @csrf
                    <button type="submit" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                        <i class="fas fa-check-double"></i>
                        <span>Verify All Matched</span>
                        <span class="px-2 py-1 bg-white/20 rounded text-xs">
                            {{ $matchingStats['matched_count'] }}
                        </span>
                    </button>
                </form>
            @endif
        @endif

        {{-- Export --}}
        <a href="{{ route('bank-statements.export', $bankStatement) }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
            <i class="fas fa-download"></i>
            <span>Export CSV</span>
        </a>

        {{-- Download PDF --}}
        <a href="{{ route('bank-statements.download', $bankStatement) }}" class="bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
            <i class="fas fa-file-pdf"></i>
            <span>Download PDF</span>
        </a>

        {{-- Delete --}}
        <form action="{{ route('bank-statements.destroy', $bankStatement) }}" method="POST" class="inline ml-auto" onsubmit="return confirm('Are you sure? This will delete all transactions!')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2 shadow-lg">
                <i class="fas fa-trash"></i>
                <span>Delete</span>
            </button>
        </form>
    </div>
</div>

{{-- Matching Statistics --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl p-4 border border-blue-500/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-blue-200 mb-1">Total Transactions</p>
                <p class="text-2xl font-bold text-white">{{ $matchingStats['total_transactions'] }}</p>
            </div>
            <i class="fas fa-receipt text-4xl text-blue-300/50"></i>
        </div>
    </div>

    <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-xl p-4 border border-green-500/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-green-200 mb-1">Matched</p>
                <p class="text-2xl font-bold text-white">{{ $matchingStats['matched_count'] }}</p>
                <p class="text-xs text-green-200">{{ number_format($matchingStats['match_percentage'], 1) }}%</p>
            </div>
            <i class="fas fa-check-circle text-4xl text-green-300/50"></i>
        </div>
    </div>

    <div class="bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-xl p-4 border border-yellow-500/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-yellow-200 mb-1">Unmatched</p>
                <p class="text-2xl font-bold text-white">{{ $matchingStats['unmatched_count'] }}</p>
            </div>
            <i class="fas fa-question-circle text-4xl text-yellow-300/50"></i>
        </div>
    </div>

    <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl p-4 border border-purple-500/50 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-purple-200 mb-1">Manual</p>
                <p class="text-2xl font-bold text-white">{{ $matchingStats['manual_count'] }}</p>
            </div>
            <i class="fas fa-hand-pointer text-4xl text-purple-300/50"></i>
        </div>
    </div>
</div>