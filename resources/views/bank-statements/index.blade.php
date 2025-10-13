<x-app-layout>
    <x-slot name="header">Bank Statements Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            <div class="bg-green-600/20 border border-green-600 text-green-400 px-6 py-4 rounded-lg flex items-center space-x-3 mb-6 alert-animate">
                <i class="fas fa-check-circle text-2xl"></i>
                <p class="font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Processing Queue Alert --}}
        @php
            $processingCount = $stats['pending'] + $stats['processing'];
        @endphp
        @if($processingCount > 0)
            <div id="processingAlert" class="bg-gradient-to-r from-yellow-600/20 to-orange-600/20 border border-yellow-500 rounded-xl p-6 mb-6 shadow-lg">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-cog fa-spin text-white text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-white font-bold text-lg mb-2">
                            OCR Processing in Progress
                        </h4>
                        <p class="text-yellow-200 text-sm mb-3">
                            {{ $processingCount }} statement(s) are being processed. This page will auto-refresh every 10 seconds.
                        </p>
                        <div class="bg-slate-900/50 rounded-lg p-3 border border-slate-700">
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="text-gray-400">Next refresh in: <span id="refreshTimer" class="text-yellow-400 font-semibold">10</span>s</span>
                                <button onclick="refreshNow()" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                                    <i class="fas fa-sync-alt mr-1"></i>Refresh Now
                                </button>
                            </div>
                            <div class="bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div id="autoRefreshProgress" class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2 rounded-full transition-all duration-1000" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Bank Statements</h2>
                <p class="text-gray-400">Upload and manage your bank statements for automatic transaction processing</p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- Clear Filters Button --}}
                @if(request()->hasAny(['company_id', 'bank_id', 'ocr_status', 'is_reconciled', 'user_id', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('bank-statements.index') }}" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-3 rounded-xl font-semibold transition-all flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                {{-- Upload Button --}}
                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('bank-statements.select-company') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                        <i class="fas fa-upload"></i>
                        <span>Upload Statement</span>
                    </a>
                @else
                    <a href="{{ route('bank-statements.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                        <i class="fas fa-upload"></i>
                        <span>Upload Statement</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- FILTERS SECTION --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white flex items-center">
                    <i class="fas fa-filter mr-2 text-blue-400"></i>
                    Filters
                </h3>
                <button onclick="toggleFilters()" class="text-gray-400 hover:text-white transition">
                    <i id="filterIcon" class="fas fa-chevron-up"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('bank-statements.index') }}" id="filterForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    {{-- SUPER ADMIN: Company Filter --}}
                    @if(auth()->user()->isSuperAdmin())
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">
                                <i class="fas fa-building mr-1"></i>Company
                            </label>
                            <select name="company_id" id="companyFilter" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Companies</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Bank Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-university mr-1"></i>Bank
                        </label>
                        <select name="bank_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Banks</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" {{ request('bank_id') == $bank->id ? 'selected' : '' }}>
                                    {{ $bank->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- OCR Status Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-tasks mr-1"></i>OCR Status
                        </label>
                        <select name="ocr_status" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            @foreach($ocrStatuses as $key => $label)
                                <option value="{{ $key }}" {{ request('ocr_status') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reconciliation Status --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-check-double mr-1"></i>Reconciled
                        </label>
                        <select name="is_reconciled" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All</option>
                            <option value="1" {{ request('is_reconciled') === '1' ? 'selected' : '' }}>Reconciled</option>
                            <option value="0" {{ request('is_reconciled') === '0' ? 'selected' : '' }}>Not Reconciled</option>
                        </select>
                    </div>

                    {{-- User Filter (Admin & Super Admin) --}}
                    @if($users && $users->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">
                                <i class="fas fa-user mr-1"></i>Uploaded By
                            </label>
                            <select name="user_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">All Users</option>
                                @foreach($users as $filterUser)
                                    <option value="{{ $filterUser->id }}" {{ request('user_id') == $filterUser->id ? 'selected' : '' }}>
                                        {{ $filterUser->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Date From --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Period From
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-calendar-check mr-1"></i>Period To
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            <i class="fas fa-search mr-1"></i>Search
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Filename, account..." class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                {{-- Filter Actions --}}
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-700">
                    <a href="{{ route('bank-statements.index') }}" class="px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-semibold transition-all">
                        <i class="fas fa-times mr-2"></i>Reset
                    </a>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-semibold transition-all">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Statements</p>
                        <p class="text-white text-3xl font-bold">{{ number_format($stats['total']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-pdf text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Processing</p>
                        <p class="text-white text-3xl font-bold">{{ number_format($stats['processing'] + $stats['pending']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Completed</p>
                        <p class="text-white text-3xl font-bold">{{ number_format($stats['completed']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Failed</p>
                        <p class="text-white text-3xl font-bold">{{ number_format($stats['failed']) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Filters Display --}}
        @if(request()->hasAny(['company_id', 'bank_id', 'ocr_status', 'is_reconciled', 'user_id', 'date_from', 'date_to', 'search']))
            <div class="bg-blue-600/10 border border-blue-500/30 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="text-blue-400 font-semibold text-sm">
                            <i class="fas fa-filter mr-1"></i>Active Filters:
                        </span>
                        
                        @if(request('company_id') && isset($companies))
                            @php $company = $companies->firstWhere('id', request('company_id')); @endphp
                            @if($company)
                                <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                    Company: {{ $company->name }}
                                </span>
                            @endif
                        @endif

                        @if(request('bank_id'))
                            @php $bank = $banks->firstWhere('id', request('bank_id')); @endphp
                            @if($bank)
                                <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                    Bank: {{ $bank->name }}
                                </span>
                            @endif
                        @endif

                        @if(request('ocr_status'))
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                Status: {{ $ocrStatuses[request('ocr_status')] ?? request('ocr_status') }}
                            </span>
                        @endif

                        @if(request('is_reconciled') !== null)
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                {{ request('is_reconciled') == '1' ? 'Reconciled' : 'Not Reconciled' }}
                            </span>
                        @endif

                        @if(request('user_id') && isset($users))
                            @php $filterUser = $users->firstWhere('id', request('user_id')); @endphp
                            @if($filterUser)
                                <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                    User: {{ $filterUser->name }}
                                </span>
                            @endif
                        @endif

                        @if(request('date_from'))
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                From: {{ \Carbon\Carbon::parse(request('date_from'))->format('d M Y') }}
                            </span>
                        @endif

                        @if(request('date_to'))
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                To: {{ \Carbon\Carbon::parse(request('date_to'))->format('d M Y') }}
                            </span>
                        @endif

                        @if(request('search'))
                            <span class="px-3 py-1 bg-blue-600/20 text-blue-300 rounded-lg text-xs font-semibold">
                                Search: "{{ request('search') }}"
                            </span>
                        @endif
                    </div>

                    <a href="{{ route('bank-statements.index') }}" class="text-blue-400 hover:text-blue-300 text-sm font-semibold transition">
                        <i class="fas fa-times mr-1"></i>Clear All
                    </a>
                </div>
            </div>
        @endif

        {{-- Statements List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">Bank Statements</h3>
                    <p class="text-sm text-gray-400">Showing {{ $statements->firstItem() ?? 0 }} to {{ $statements->lastItem() ?? 0 }} of {{ $statements->total() }} results</p>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($statements as $statement)
                        <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition statement-card" data-status="{{ $statement->ocr_status }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    {{-- Bank Logo/Icon --}}
                                    <div class="w-16 h-16 bg-slate-700 rounded-xl flex items-center justify-center flex-shrink-0">
                                        @if($statement->bank->logo)
                                            <img src="{{ asset('storage/' . $statement->bank->logo) }}" alt="{{ $statement->bank->name }}" class="w-12 h-12 object-contain">
                                        @else
                                            <i class="fas fa-university text-gray-400 text-2xl"></i>
                                        @endif
                                    </div>

                                    {{-- Statement Info --}}
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2 flex-wrap gap-2">
                                            <h4 class="text-white font-semibold text-lg">
                                                {{ $statement->original_filename }}
                                            </h4>
                                            
                                            @php
                                                $statusConfig = [
                                                    'pending' => ['color' => 'blue', 'icon' => 'clock', 'text' => 'Pending'],
                                                    'processing' => ['color' => 'yellow', 'icon' => 'spinner', 'text' => 'Processing'],
                                                    'completed' => ['color' => 'green', 'icon' => 'check-circle', 'text' => 'Completed'],
                                                    'failed' => ['color' => 'red', 'icon' => 'exclamation-circle', 'text' => 'Failed'],
                                                ];
                                                $status = $statusConfig[$statement->ocr_status] ?? $statusConfig['pending'];
                                            @endphp
                                            
                                            <span class="px-3 py-1 bg-{{ $status['color'] }}-600/20 text-{{ $status['color'] }}-400 rounded-lg text-xs font-semibold border border-{{ $status['color'] }}-500/30">
                                                <i class="fas fa-{{ $status['icon'] }} mr-1 {{ $statement->ocr_status === 'processing' ? 'fa-spin' : '' }}"></i>
                                                {{ $status['text'] }}
                                            </span>

                                            {{-- Super Admin: Show Company Badge --}}
                                            @if(auth()->user()->isSuperAdmin())
                                                <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-lg text-xs font-semibold border border-purple-500/30">
                                                    <i class="fas fa-building mr-1"></i>
                                                    {{ $statement->company->name }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-400 flex-wrap gap-y-2">
                                            <span>
                                                <i class="fas fa-university mr-1"></i>
                                                {{ $statement->bank->name }}
                                            </span>
                                            <span>
                                                <i class="fas fa-user mr-1"></i>
                                                {{ $statement->user->name }}
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                {{ $statement->uploaded_at->format('d M Y H:i') }}
                                            </span>
                                            @if($statement->total_transactions > 0)
                                                <span>
                                                    <i class="fas fa-list mr-1"></i>
                                                    {{ $statement->total_transactions }} transactions
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Processing Progress Bar --}}
                                        @if(in_array($statement->ocr_status, ['pending', 'processing']))
                                            <div class="mt-3 bg-slate-800 rounded-full h-2 overflow-hidden">
                                                <div class="processing-bar bg-gradient-to-r from-yellow-500 via-orange-500 to-yellow-500 h-2 rounded-full animate-pulse" style="width: {{ $statement->ocr_status === 'pending' ? '30' : '70' }}%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                @if($statement->ocr_status === 'pending')
                                                    Queued for processing...
                                                @else
                                                    Processing OCR and extracting transactions...
                                                @endif
                                            </p>
                                        @endif

                                        {{-- Completed Info --}}
                                        @if($statement->ocr_status === 'completed' && $statement->ocr_completed_at)
                                            <div class="mt-2 text-xs text-green-400">
                                                <i class="fas fa-check mr-1"></i>
                                                Completed at {{ $statement->ocr_completed_at->format('d M Y H:i') }}
                                            </div>
                                        @endif

                                        {{-- Error Info --}}
                                        @if($statement->ocr_status === 'failed' && $statement->ocr_error)
                                            <div class="mt-2 bg-red-600/20 border border-red-500/30 rounded-lg p-3">
                                                <p class="text-xs text-red-400">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    {{ Str::limit($statement->ocr_error, 100) }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center space-x-2 flex-shrink-0">
                                    <a href="{{ route('bank-statements.show', $statement) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($statement->ocr_status === 'completed')
                                        <button onclick="processMatching({{ $statement->id }})" class="p-3 bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded-lg transition-all" title="Process Matching">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    @endif

                                    @if($statement->ocr_status === 'failed')
                                        <button onclick="reprocessOCR({{ $statement->id }})" class="p-3 bg-orange-600/20 text-orange-400 hover:bg-orange-600 hover:text-white rounded-lg transition-all" title="Retry OCR">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endif
                                    
                                    <a href="{{ route('bank-statements.download', $statement) }}" class="p-3 bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white rounded-lg transition-all" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button onclick="confirmDelete({{ $statement->id }}, '{{ $statement->original_filename }}')" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-file-pdf text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg mb-2">No bank statements found</p>
                            @if(request()->hasAny(['company_id', 'bank_id', 'ocr_status', 'is_reconciled', 'user_id', 'date_from', 'date_to', 'search']))
                                <p class="text-gray-500 text-sm mb-6">Try adjusting your filters</p>
                                <a href="{{ route('bank-statements.index') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                    <i class="fas fa-times"></i>
                                    <span>Clear Filters</span>
                                </a>
                            @else
                                <p class="text-gray-500 text-sm mb-6">Upload your first bank statement to get started</p>
                                <a href="{{ route('bank-statements.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload First Statement</span>
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
            @if($statements->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $statements->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Bank Statement?</h3>
                    <p class="text-gray-400 mb-4">Delete statement <span id="deleteStatementName" class="text-white font-semibold"></span>?</p>
                    <p class="text-yellow-400 text-sm">
                        <i class="fas fa-info-circle mr-1"></i>This will also delete all associated transactions
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                    <button id="confirmDeleteBtn" onclick="submitDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden forms for actions --}}
    @foreach($statements as $statement)
        <form id="delete-form-{{ $statement->id }}" action="{{ route('bank-statements.destroy', $statement) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        <form id="reprocess-form-{{ $statement->id }}" action="{{ route('bank-statements.reprocess', $statement) }}" method="POST" class="hidden">
            @csrf
        </form>
        <form id="matching-form-{{ $statement->id }}" action="{{ route('bank-statements.match-transactions', $statement) }}" method="POST" class="hidden">
            @csrf
        </form>
    @endforeach

    @push('scripts')
    <script>
        let deleteFormId = null;
        let refreshInterval = null;
        let timerInterval = null;
        let secondsLeft = 10;

        // Toggle Filters
        function toggleFilters() {
            const form = document.getElementById('filterForm');
            const icon = document.getElementById('filterIcon');
            
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                form.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        // Auto-submit on company change (Super Admin)
        @if(auth()->user()->isSuperAdmin())
        document.getElementById('companyFilter')?.addEventListener('change', function() {
            // Reload users based on selected company
            document.getElementById('filterForm').submit();
        });
        @endif

        // Delete Modal Functions
        function confirmDelete(id, filename) {
            deleteFormId = id;
            document.getElementById('deleteStatementName').textContent = filename;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteFormId = null;
        }

        function submitDelete() {
            if (deleteFormId) {
                document.getElementById('delete-form-' + deleteFormId).submit();
            }
        }

        // Reprocess OCR
        function reprocessOCR(id) {
            if (confirm('Retry OCR processing for this statement?')) {
                document.getElementById('reprocess-form-' + id).submit();
            }
        }

        // Process Matching
        function processMatching(id) {
            if (confirm('Start transaction matching for this statement?')) {
                document.getElementById('matching-form-' + id).submit();
            }
        }

        // Refresh Now
        function refreshNow() {
            window.location.reload();
        }

        // Auto refresh functionality
        const hasProcessing = {{ $processingCount > 0 ? 'true' : 'false' }};
        
        if (hasProcessing) {
            // Update timer display
            timerInterval = setInterval(() => {
                secondsLeft--;
                const timerEl = document.getElementById('refreshTimer');
                const progressEl = document.getElementById('autoRefreshProgress');
                
                if (timerEl) {
                    timerEl.textContent = secondsLeft;
                }
                
                if (progressEl) {
                    const progress = ((10 - secondsLeft) / 10) * 100;
                    progressEl.style.width = progress + '%';
                }
                
                if (secondsLeft <= 0) {
                    window.location.reload();
                }
            }, 1000);

            // Animate processing bars
            const processingBars = document.querySelectorAll('.processing-bar');
            processingBars.forEach(bar => {
                setInterval(() => {
                    const currentWidth = parseInt(bar.style.width);
                    if (currentWidth < 90) {
                        bar.style.width = (currentWidth + 1) + '%';
                    }
                }, 500);
            });
        }

        // Auto-hide success messages
        setTimeout(() => {
            const successAlerts = document.querySelectorAll('.alert-animate');
            successAlerts.forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 8000);

        // Cleanup intervals on page unload
        window.addEventListener('beforeunload', () => {
            if (timerInterval) clearInterval(timerInterval);
            if (refreshInterval) clearInterval(refreshInterval);
        });
    </script>
    @endpush
</x-app-layout>