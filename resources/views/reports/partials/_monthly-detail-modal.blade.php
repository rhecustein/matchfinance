{{-- 
    Partial: Monthly Transaction Detail
    Usage: @include('reports.partials._monthly-detail', ['year' => $year, 'month' => $month, 'bank' => $bank])
--}}

<div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            @if($bank->logo)
                <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="h-12 w-12 object-contain rounded-lg bg-white p-2">
            @else
                <div class="h-12 w-12 bg-slate-700 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">{{ substr($bank->code, 0, 3) }}</span>
                </div>
            @endif
            <div>
                <h3 class="text-xl font-bold text-white">{{ $bank->name }}</h3>
                <p class="text-gray-400 text-sm">{{ $monthName }} {{ $year }}</p>
            </div>
        </div>
        <a href="{{ route('reports.monthly-detail', [
            'year' => $year,
            'month' => $month,
            'bank_id' => $bank->id,
            'transaction_type' => $transactionType ?? 'all',
            'company_id' => $selectedCompanyId ?? null
        ]) }}" 
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center space-x-2">
            <i class="fas fa-external-link-alt"></i>
            <span>View Full Details</span>
        </a>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
            <p class="text-gray-400 text-xs mb-1">Total Amount</p>
            <p class="text-white font-bold text-lg">Rp {{ number_format($summary['total'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700">
            <p class="text-gray-400 text-xs mb-1">Transactions</p>
            <p class="text-white font-bold text-lg">{{ number_format($summary['count']) }}</p>
        </div>
        <div class="bg-green-900/20 rounded-xl p-4 border border-green-700">
            <p class="text-gray-400 text-xs mb-1">Matched</p>
            <p class="text-green-400 font-bold text-lg">{{ number_format($summary['matched']) }}</p>
        </div>
        <div class="bg-red-900/20 rounded-xl p-4 border border-red-700">
            <p class="text-gray-400 text-xs mb-1">Unmatched</p>
            <p class="text-red-400 font-bold text-lg">{{ number_format($summary['unmatched']) }}</p>
        </div>
    </div>

    {{-- Transactions Preview (Top 10) --}}
    <div class="overflow-hidden rounded-xl border border-slate-700">
        <table class="w-full text-sm">
            <thead class="bg-slate-900/80">
                <tr>
                    <th class="px-4 py-3 text-left text-gray-400 font-semibold">Date</th>
                    <th class="px-4 py-3 text-left text-gray-400 font-semibold">Description</th>
                    <th class="px-4 py-3 text-right text-gray-400 font-semibold">Amount</th>
                    <th class="px-4 py-3 text-center text-gray-400 font-semibold">Type</th>
                    <th class="px-4 py-3 text-center text-gray-400 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @forelse($transactions->take(10) as $transaction)
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="px-4 py-3 text-gray-300 whitespace-nowrap">
                            {{ $transaction->transaction_date->format('d M') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-white text-sm truncate max-w-xs">{{ $transaction->description }}</p>
                            @if($transaction->matchedKeyword)
                                <p class="text-blue-400 text-xs mt-1">
                                    <i class="fas fa-tag"></i> {{ $transaction->matchedKeyword->keyword }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <span class="text-white font-semibold">
                                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($transaction->transaction_type === 'credit')
                                <span class="inline-block bg-green-900/30 text-green-400 px-2 py-1 rounded text-xs font-semibold">
                                    <i class="fas fa-arrow-down"></i> Credit
                                </span>
                            @else
                                <span class="inline-block bg-red-900/30 text-red-400 px-2 py-1 rounded text-xs font-semibold">
                                    <i class="fas fa-arrow-up"></i> Debit
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($transaction->matched_keyword_id)
                                <span class="inline-block bg-blue-900/30 text-blue-400 text-xs px-2 py-1 rounded">
                                    <i class="fas fa-check"></i>
                                </span>
                            @else
                                <span class="inline-block bg-yellow-900/30 text-yellow-400 text-xs px-2 py-1 rounded">
                                    <i class="fas fa-exclamation"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>No transactions found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->count() > 10)
        <div class="mt-4 text-center">
            <p class="text-gray-400 text-sm">
                Showing 10 of {{ number_format($transactions->count()) }} transactions
            </p>
        </div>
    @endif
</div>