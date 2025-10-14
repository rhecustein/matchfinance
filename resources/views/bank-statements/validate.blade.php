{{-- resources/views/bank-statements/validate.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                   class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-check-circle text-blue-400 mr-2"></i>
                        Validate Transactions
                    </h2>
                    <p class="text-sm text-gray-400 mt-1">
                        {{ $bankStatement->bank->name ?? 'Unknown Bank' }} - 
                        {{ $bankStatement->original_filename }}
                    </p>
                </div>
            </div>
            
            {{-- Progress Badge --}}
            <div class="flex items-center space-x-3">
                <div class="text-right">
                    <div class="text-2xl font-bold text-white">
                        {{ $stats['verified'] }}/{{ $stats['total'] }}
                    </div>
                    <div class="text-xs text-gray-400">Verified</div>
                </div>
                <div class="w-16 h-16">
                    <svg class="transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-700" stroke="currentColor" stroke-width="3" fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-green-500" stroke="currentColor" stroke-width="3" fill="none"
                            stroke-dasharray="{{ $stats['progress'] }}, 100"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-gray-400 text-xs mb-1">Total</div>
                    <div class="text-2xl font-bold text-white">{{ $stats['total'] }}</div>
                </div>
                
                <div class="bg-green-900/20 border border-green-700 rounded-lg p-4">
                    <div class="text-green-400 text-xs mb-1">
                        <i class="fas fa-check-circle mr-1"></i>Verified
                    </div>
                    <div class="text-2xl font-bold text-green-400">{{ $stats['verified'] }}</div>
                </div>
                
                <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-4">
                    <div class="text-yellow-400 text-xs mb-1">
                        <i class="fas fa-clock mr-1"></i>Pending
                    </div>
                    <div class="text-2xl font-bold text-yellow-400">{{ $stats['pending'] }}</div>
                </div>
                
                <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-4">
                    <div class="text-blue-400 text-xs mb-1">
                        <i class="fas fa-star mr-1"></i>High
                    </div>
                    <div class="text-2xl font-bold text-blue-400">{{ $stats['high_confidence'] }}</div>
                </div>
                
                <div class="bg-purple-900/20 border border-purple-700 rounded-lg p-4">
                    <div class="text-purple-400 text-xs mb-1">
                        <i class="fas fa-star-half-alt mr-1"></i>Medium
                    </div>
                    <div class="text-2xl font-bold text-purple-400">{{ $stats['medium_confidence'] }}</div>
                </div>
                
                <div class="bg-orange-900/20 border border-orange-700 rounded-lg p-4">
                    <div class="text-orange-400 text-xs mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Low
                    </div>
                    <div class="text-2xl font-bold text-orange-400">{{ $stats['low_confidence'] }}</div>
                </div>
                
                <div class="bg-red-900/20 border border-red-700 rounded-lg p-4">
                    <div class="text-red-400 text-xs mb-1">
                        <i class="fas fa-times-circle mr-1"></i>No Match
                    </div>
                    <div class="text-2xl font-bold text-red-400">{{ $stats['no_match'] }}</div>
                </div>
                
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-gray-400 text-xs mb-1">Progress</div>
                    <div class="text-2xl font-bold text-white">{{ $stats['progress'] }}%</div>
                </div>
            </div>

            {{-- Filter Tabs --}}
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-6">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'all']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter', 'all') === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-list mr-2"></i>All ({{ $stats['total'] }})
                    </a>
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'pending']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter') === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-clock mr-2"></i>Pending ({{ $stats['pending'] }})
                    </a>
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'high-confidence']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter') === 'high-confidence' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-star mr-2"></i>High Confidence ({{ $stats['high_confidence'] }})
                    </a>
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'low-confidence']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter') === 'low-confidence' ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Low Confidence ({{ $stats['low_confidence'] }})
                    </a>
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'no-match']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter') === 'no-match' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-times-circle mr-2"></i>No Match ({{ $stats['no_match'] }})
                    </a>
                    <a href="{{ route('bank-statements.validate', ['bankStatement' => $bankStatement, 'filter' => 'approved']) }}" 
                       class="px-4 py-2 rounded-lg transition-colors {{ request('filter') === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                        <i class="fas fa-check-circle mr-2"></i>Approved ({{ $stats['verified'] }})
                    </a>
                </div>
            </div>

            {{-- Info Alert --}}
            @if($stats['pending'] > 0)
            <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-200">
                        <p class="font-semibold mb-1">How to validate transactions:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-300">
                            <li>Review the <strong>suggested keyword & category</strong> for each transaction</li>
                            <li>Click <strong class="text-green-400">✓ Approve</strong> if correct, or <strong class="text-red-400">✗ Reject</strong> if wrong</li>
                            <li>If rejected, a search dropdown will appear to select the correct keyword</li>
                            <li>Focus on <strong>High Confidence</strong> matches first for faster validation</li>
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Transactions List --}}
            <div class="space-y-3">
                @forelse($transactions as $transaction)
                <div class="bg-gray-800 rounded-lg border border-gray-700 hover:border-gray-600 transition-all duration-200 transaction-row" 
                     id="transaction-{{ $transaction->id }}"
                     data-transaction-id="{{ $transaction->id }}">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Left: Transaction Info --}}
                            <div class="flex-1 min-w-0">
                                {{-- Date & Amount --}}
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-xs text-gray-400 font-mono">
                                        {{ $transaction->transaction_date->format('d M Y') }}
                                        @if($transaction->transaction_time)
                                            {{ \Carbon\Carbon::parse($transaction->transaction_time)->format('H:i') }}
                                        @endif
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $transaction->transaction_type === 'debit' ? 'bg-red-900/30 text-red-400' : 'bg-green-900/30 text-green-400' }}">
                                        {{ strtoupper($transaction->transaction_type) }}
                                    </span>
                                    <span class="text-lg font-bold {{ $transaction->transaction_type === 'debit' ? 'text-red-400' : 'text-green-400' }}">
                                        {{ $transaction->formatted_amount }}
                                    </span>
                                </div>

                                {{-- Description --}}
                                <div class="text-white font-medium mb-3 break-words">
                                    {{ $transaction->description }}
                                </div>

                                {{-- Current Assignment/Suggestion --}}
                                @if($transaction->matched_keyword_id && $transaction->matchedKeyword)
                                <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700 mb-3">
                                    <div class="flex items-start gap-3">
                                        {{-- Confidence Badge --}}
                                        <div class="flex-shrink-0">
                                            @php
                                                $confidenceClass = 'bg-gray-700 text-gray-400';
                                                $confidenceIcon = 'fa-question-circle';
                                                $confidenceText = 'Unknown';
                                                
                                                if($transaction->confidence_score >= 80) {
                                                    $confidenceClass = 'bg-green-900/30 text-green-400 border border-green-700';
                                                    $confidenceIcon = 'fa-star';
                                                    $confidenceText = 'High';
                                                } elseif($transaction->confidence_score >= 50) {
                                                    $confidenceClass = 'bg-yellow-900/30 text-yellow-400 border border-yellow-700';
                                                    $confidenceIcon = 'fa-star-half-alt';
                                                    $confidenceText = 'Medium';
                                                } elseif($transaction->confidence_score > 0) {
                                                    $confidenceClass = 'bg-orange-900/30 text-orange-400 border border-orange-700';
                                                    $confidenceIcon = 'fa-exclamation-triangle';
                                                    $confidenceText = 'Low';
                                                }
                                            @endphp
                                            
                                            <div class="px-3 py-1 rounded-full text-xs font-semibold {{ $confidenceClass }}">
                                                <i class="fas {{ $confidenceIcon }} mr-1"></i>
                                                {{ $confidenceText }} ({{ $transaction->confidence_score }}%)
                                            </div>
                                        </div>

                                        {{-- Suggested Category Hierarchy --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs text-gray-400 mb-2">
                                                <i class="fas fa-lightbulb mr-1"></i>
                                                @if($transaction->is_verified)
                                                    <span class="text-green-400">✓ Verified Assignment</span>
                                                @else
                                                    <span class="text-yellow-400">💡 Suggested Category (Please Review)</span>
                                                @endif
                                            </div>
                                            
                                            {{-- Keyword dengan ID --}}
                                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                                <span class="px-2 py-1 bg-blue-900/30 text-blue-400 rounded border border-blue-700 font-semibold text-sm">
                                                    🎯 {{ $transaction->matchedKeyword->keyword }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    (Keyword ID: {{ $transaction->matched_keyword_id }})
                                                </span>
                                            </div>
                                            
                                            {{-- Full Category Hierarchy dengan IDs --}}
                                            <div class="flex items-center gap-2 flex-wrap text-sm">
                                                {{-- Type --}}
                                                @if($transaction->type_id && $transaction->type)
                                                <div class="flex items-center gap-1">
                                                    <span class="px-2 py-1 bg-purple-900/30 text-purple-400 rounded border border-purple-700">
                                                        {{ $transaction->type->name }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">(Type: {{ $transaction->type_id }})</span>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                                                @endif
                                                
                                                {{-- Category --}}
                                                @if($transaction->category_id && $transaction->category)
                                                <div class="flex items-center gap-1">
                                                    <span class="px-2 py-1 bg-pink-900/30 text-pink-400 rounded border border-pink-700">
                                                        {{ $transaction->category->name }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">(Cat: {{ $transaction->category_id }})</span>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                                                @endif
                                                
                                                {{-- Sub Category --}}
                                                @if($transaction->sub_category_id && $transaction->subCategory)
                                                <div class="flex items-center gap-1">
                                                    <span class="px-2 py-1 bg-cyan-900/30 text-cyan-400 rounded border border-cyan-700">
                                                        {{ $transaction->subCategory->name }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">(SubCat: {{ $transaction->sub_category_id }})</span>
                                                </div>
                                                @endif
                                            </div>

                                            {{-- Matching Reason --}}
                                            @if($transaction->matching_reason)
                                            <div class="mt-2 text-xs text-gray-500 italic">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                {{ $transaction->matching_reason }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @else
                                {{-- No Match Case --}}
                                <div class="bg-red-900/20 border border-red-700 rounded-lg p-3 mb-3">
                                    <div class="flex items-center text-red-400">
                                        <i class="fas fa-times-circle mr-2"></i>
                                        <span class="text-sm font-semibold">No keyword match found</span>
                                    </div>
                                    <p class="text-xs text-red-300 mt-1">
                                        No IDs: matched_keyword_id, sub_category_id, category_id, type_id = NULL
                                    </p>
                                </div>
                                @endif

                                {{-- Alternative Suggestions --}}
                                @if(!$transaction->is_verified && $transaction->matchingLogs && $transaction->matchingLogs->count() > 1)
                                <div class="mt-2">
                                    <details class="text-xs">
                                        <summary class="text-gray-400 hover:text-gray-300 cursor-pointer">
                                            <i class="fas fa-list mr-1"></i>
                                            View {{ $transaction->matchingLogs->count() - 1 }} alternative suggestion(s)
                                        </summary>
                                        <div class="mt-2 space-y-1 pl-4 bg-gray-900/30 rounded p-2">
                                            @foreach($transaction->matchingLogs->skip(1) as $log)
                                            <div class="text-gray-400 flex items-center gap-2 py-1">
                                                <span class="px-2 py-0.5 bg-gray-700 rounded text-xs font-semibold">{{ $log->confidence_score }}%</span>
                                                <span class="font-medium">{{ $log->keyword->keyword ?? 'Unknown' }}</span>
                                                @if($log->keyword && $log->keyword->subCategory)
                                                <i class="fas fa-arrow-right text-gray-600 text-xs"></i>
                                                <span class="text-gray-500">
                                                    {{ $log->keyword->subCategory->category->type->name ?? '' }} →
                                                    {{ $log->keyword->subCategory->category->name ?? '' }} →
                                                    {{ $log->keyword->subCategory->name }}
                                                </span>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </div>
                                @endif
                            </div>

                            {{-- Right: Actions --}}
                            <div class="flex-shrink-0 flex flex-col gap-2 min-w-[300px]">
                                @if($transaction->is_verified)
                                    {{-- Already Verified State --}}
                                    <div class="bg-green-900/30 border border-green-700 rounded-lg p-3 text-center">
                                        <i class="fas fa-check-circle text-green-400 text-2xl mb-1"></i>
                                        <div class="text-green-400 font-semibold text-sm">Verified ✓</div>
                                        <div class="text-green-300 text-xs mt-1">
                                            @if($transaction->is_manual_category)
                                                <i class="fas fa-hand-pointer mr-1"></i>Manual Assignment
                                            @else
                                                <i class="fas fa-magic mr-1"></i>Auto Approved
                                            @endif
                                        </div>
                                        @if($transaction->verifiedBy)
                                        <div class="text-gray-400 text-xs mt-1">
                                            by {{ $transaction->verifiedBy->name }}
                                        </div>
                                        @endif
                                        @if($transaction->verified_at)
                                        <div class="text-gray-500 text-xs mt-1">
                                            {{ $transaction->verified_at->format('d M Y H:i') }}
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    {{-- Pending Validation State --}}
                                    
                                    @if($transaction->matched_keyword_id)
                                        {{-- HAS SUGGESTION: Show Approve & Reject Buttons --}}
                                        <div class="action-buttons-container">
                                            <div class="flex gap-2">
                                                <button type="button" 
                                                        class="approve-btn flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center gap-2"
                                                        data-transaction-id="{{ $transaction->id }}">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span>Approve</span>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="reject-btn flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center gap-2"
                                                        data-transaction-id="{{ $transaction->id }}">
                                                    <i class="fas fa-times-circle"></i>
                                                    <span>Reject</span>
                                                </button>
                                            </div>
                                            
                                            <div class="text-xs text-center text-gray-400 mt-2">
                                                Review suggestion above
                                            </div>
                                        </div>
                                        
                                        {{-- Hidden Select2 (will show after Reject) --}}
                                        <div class="search-container hidden">
                                            <select class="keyword-select w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                                    data-transaction-id="{{ $transaction->id }}"
                                                    data-placeholder="🔍 Search & select correct keyword...">
                                                <option value="">-- Select Correct Keyword --</option>
                                            </select>
                                            
                                            <button type="button" 
                                                    class="cancel-search-btn w-full mt-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition-colors"
                                                    data-transaction-id="{{ $transaction->id }}">
                                                <i class="fas fa-arrow-left mr-2"></i>Cancel & Back
                                            </button>
                                        </div>
                                    @else
                                        {{-- NO MATCH: Show Search Immediately --}}
                                        <div class="search-container">
                                            <div class="bg-orange-900/20 border border-orange-700 rounded-lg p-2 mb-2 text-center">
                                                <span class="text-xs text-orange-300">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    No suggestion - Manual selection required
                                                </span>
                                            </div>
                                            
                                            <select class="keyword-select w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                                    data-transaction-id="{{ $transaction->id }}"
                                                    data-placeholder="🔍 Search & select keyword...">
                                                <option value="">-- Select Keyword --</option>
                                            </select>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                    <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-400 mb-2">No Transactions Found</h3>
                    <p class="text-gray-500">Try changing the filter to view different transactions</p>
                </div>
                @endforelse
            </div>

            {{-- Back Button --}}
            <div class="mt-6 flex justify-between items-center">
                <a href="{{ route('bank-statements.show', $bankStatement) }}" 
                   class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Statement
                </a>

                @if($stats['pending'] === 0 && $stats['total'] > 0)
                <div class="bg-green-900/30 border border-green-700 rounded-lg px-6 py-3">
                    <i class="fas fa-check-circle text-green-400 mr-2"></i>
                    <span class="text-green-400 font-semibold">All transactions validated!</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    @push('scripts')
    {{-- jQuery FIRST --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    {{-- Select2 AFTER jQuery --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        jQuery(document).ready(function($) {
            console.log('✅ jQuery loaded:', typeof $ !== 'undefined');
            console.log('✅ Select2 loaded:', typeof $.fn.select2 !== 'undefined');
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Initialize Select2 untuk semua dropdown
            function initializeSelect2($select) {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                
                const transactionId = $select.data('transaction-id');
                
                $select.select2({
                    ajax: {
                        url: '{{ route("bank-statements.keywords.search") }}',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            if (!data.results || data.results.length === 0) {
                                return {
                                    results: [{
                                        id: '',
                                        text: 'No keywords found',
                                        disabled: true
                                    }]
                                };
                            }
                            return {
                                results: data.results,
                                pagination: data.pagination
                            };
                        },
                        cache: true
                    },
                    placeholder: '🔍 Search & select keyword...',
                    allowClear: true,
                    minimumInputLength: 0,
                    width: '100%',
                    templateResult: formatKeywordResult,
                    templateSelection: formatKeywordSelection,
                    language: {
                        searching: function() { return '🔍 Searching...'; },
                        noResults: function() { return '❌ No keywords found'; }
                    }
                });

                $select.on('select2:select', function(e) {
                    handleManualKeywordSelection(transactionId, e.params.data.id, e.params.data);
                });
            }

            // Initialize visible selects only
            $('.keyword-select:visible').each(function() {
                initializeSelect2($(this));
            });

            function formatKeywordResult(keyword) {
                if (keyword.loading) {
                    return $('<div class="text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</div>');
                }
                if (!keyword.id || keyword.disabled) {
                    return keyword.text;
                }

                return $(`
                    <div class="p-2">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-purple-900/50 text-purple-300 rounded text-xs font-semibold">
                                    ${keyword.keyword || keyword.text}
                                </span>
                                ${keyword.priority ? `<span class="text-xs text-gray-400">Priority: ${keyword.priority}</span>` : ''}
                            </div>
                            ${keyword.category_path ? `<div class="text-xs text-gray-400">${keyword.category_path}</div>` : ''}
                        </div>
                    </div>
                `);
            }

            function formatKeywordSelection(keyword) {
                return keyword.keyword || keyword.text;
            }

            // ========================================
            // APPROVE BUTTON - Auto approve suggestion
            // ========================================
            $(document).on('click', '.approve-btn', function() {
                const $btn = $(this);
                const transactionId = $btn.data('transaction-id');
                const $row = $(`#transaction-${transactionId}`);
                
                $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Approving...');
                
                $.ajax({
                    url: `{{ url('statement-transactions') }}/${transactionId}/approve`,
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', '✓ ' + response.message);
                            updateRowToVerified($row, response.data, false);
                            setTimeout(() => location.reload(), 1500);
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.message || 'Failed to approve');
                        $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> <span>Approve</span>');
                    }
                });
            });

            // ========================================
            // REJECT BUTTON - Show search dropdown
            // ========================================
            $(document).on('click', '.reject-btn', function() {
                const transactionId = $(this).data('transaction-id');
                const $row = $(`#transaction-${transactionId}`);
                const $buttonsContainer = $row.find('.action-buttons-container');
                const $searchContainer = $row.find('.search-container');
                
                // Hide buttons, show search
                $buttonsContainer.hide();
                $searchContainer.removeClass('hidden').show();
                
                // Initialize Select2 untuk dropdown yang baru dimunculkan
                const $select = $searchContainer.find('.keyword-select');
                initializeSelect2($select);
                
                // Focus on search
                setTimeout(() => {
                    $select.select2('open');
                }, 100);
                
                showNotification('info', 'Please select the correct keyword from the dropdown');
            });

            // ========================================
            // CANCEL SEARCH BUTTON - Back to approve/reject
            // ========================================
            $(document).on('click', '.cancel-search-btn', function() {
                const transactionId = $(this).data('transaction-id');
                const $row = $(`#transaction-${transactionId}`);
                const $buttonsContainer = $row.find('.action-buttons-container');
                const $searchContainer = $row.find('.search-container');
                
                // Show buttons, hide search
                $searchContainer.hide();
                $buttonsContainer.show();
                
                // Clear selection
                $searchContainer.find('.keyword-select').val(null).trigger('change');
            });

            // ========================================
            // MANUAL KEYWORD SELECTION
            // ========================================
            function handleManualKeywordSelection(transactionId, keywordId, keywordData) {
                const $row = $(`#transaction-${transactionId}`);
                const $select = $row.find('.keyword-select');
                $select.prop('disabled', true);
                
                $.ajax({
                    url: `{{ url('statement-transactions') }}/${transactionId}/set-keyword`,
                    method: 'POST',
                    data: { keyword_id: keywordId },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', '✓ ' + response.message);
                            updateRowToVerified($row, response.data, true);
                            setTimeout(() => location.reload(), 1500);
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', xhr.responseJSON?.message || 'Failed to assign keyword');
                        $select.prop('disabled', false).val(null).trigger('change');
                    }
                });
            }

            // ========================================
            // UPDATE ROW TO VERIFIED STATE
            // ========================================
            function updateRowToVerified($row, data, isManual) {
                const html = `
                    <div class="bg-green-900/30 border border-green-700 rounded-lg p-3 text-center">
                        <i class="fas fa-check-circle text-green-400 text-2xl mb-1"></i>
                        <div class="text-green-400 font-semibold text-sm">Verified ✓</div>
                        <div class="text-green-300 text-xs mt-1">
                            ${isManual ? '<i class="fas fa-hand-pointer mr-1"></i>Manual Selection' : '<i class="fas fa-magic mr-1"></i>Auto Approved'}
                        </div>
                        <div class="text-gray-400 text-xs mt-1">by ${data.verified_by || 'You'}</div>
                    </div>
                `;
                $row.find('.flex-shrink-0').html(html);
                $row.addClass('border-green-700');
                setTimeout(() => $row.removeClass('border-green-700'), 2000);
            }

            // ========================================
            // NOTIFICATION HELPER
            // ========================================
            function showNotification(type, message) {
                const bgColors = {
                    'success': 'bg-green-600',
                    'error': 'bg-red-600',
                    'info': 'bg-blue-600'
                };
                const icons = {
                    'success': 'fa-check-circle',
                    'error': 'fa-exclamation-circle',
                    'info': 'fa-info-circle'
                };
                
                const bgColor = bgColors[type] || 'bg-gray-600';
                const icon = icons[type] || 'fa-info-circle';
                
                const notification = $(`
                    <div class="fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3 max-w-md">
                        <i class="fas ${icon}"></i>
                        <span>${message}</span>
                    </div>
                `);
                
                $('body').append(notification);
                setTimeout(() => notification.fadeOut(300, function() { $(this).remove(); }), 3000);
            }
        });
    </script>
    @endpush

    {{-- Styles --}}
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            background-color: #374151 !important;
            border: 1px solid #4B5563 !important;
            border-radius: 0.5rem !important;
            height: 48px !important;
            padding: 8px 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff !important;
            line-height: 32px !important;
            padding-left: 12px !important;
        }
        .select2-dropdown {
            background-color: #1F2937 !important;
            border: 1px solid #4B5563 !important;
            border-radius: 0.5rem !important;
        }
        .select2-container--default .select2-results__option {
            color: #D1D5DB !important;
            padding: 12px 16px !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #7C3AED !important;
            color: #fff !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #374151 !important;
            border: 1px solid #4B5563 !important;
            color: #fff !important;
            border-radius: 0.375rem !important;
            padding: 8px 12px !important;
        }
        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .hidden {
            display: none !important;
        }
    </style>
    @endpush
</x-app-layout>