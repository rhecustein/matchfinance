<x-app-layout>
    <x-slot name="header">
        Bank Details
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('banks.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-university mr-1"></i>Banks
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $bank->name }}</span>
            </nav>
        </div>

        <!-- Bank Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                @if($bank->logo)
                    <div class="w-24 h-24 bg-white rounded-xl p-3 shadow-lg">
                        <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="w-full h-full object-contain">
                    </div>
                @else
                    <div class="w-24 h-24 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-university text-white text-4xl"></i>
                    </div>
                @endif
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-white mb-2">{{ $bank->name }}</h2>
                    <p class="text-blue-100 text-lg mb-4">Code: {{ $bank->code }}</p>
                    <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                        @if($bank->is_active)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-green-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-check-circle"></i>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-red-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-times-circle"></i>
                                <span>Inactive</span>
                            </span>
                        @endif
                        <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Since {{ $bank->created_at->format('M Y') }}</span>
                        </span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <form action="{{ route('banks.toggle-active', $bank) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Toggle Status</span>
                        </button>
                    </form>
                    <a href="{{ route('banks.edit', $bank) }}" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                        <i class="fas fa-edit"></i>
                        <span>Edit Bank</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Total Statements</p>
                <p class="text-white text-3xl font-bold">{{ $stats['total_statements'] }}</p>
                <p class="text-gray-500 text-xs mt-1">All time</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Completed</p>
                <p class="text-white text-3xl font-bold">{{ $stats['completed'] }}</p>
                <p class="text-gray-500 text-xs mt-1">
                    {{ $stats['total_statements'] > 0 ? round(($stats['completed'] / $stats['total_statements']) * 100) : 0 }}% success rate
                </p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-spinner text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Processing</p>
                <p class="text-white text-3xl font-bold">{{ $stats['processing'] }}</p>
                <p class="text-gray-500 text-xs mt-1">In progress</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Pending</p>
                <p class="text-white text-3xl font-bold">{{ $stats['pending'] }}</p>
                <p class="text-gray-500 text-xs mt-1">Waiting</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Failed</p>
                <p class="text-white text-3xl font-bold">{{ $stats['failed'] }}</p>
                <p class="text-gray-500 text-xs mt-1">Need attention</p>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Recent Statements -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-history mr-3 text-blue-500"></i>
                            Recent Bank Statements
                        </h3>
                        @if($bank->bankStatements->count() > 0)
                            <a href="{{ route('bank-statements.index') }}?bank={{ $bank->id }}" class="text-blue-500 hover:text-blue-400 text-sm font-semibold">
                                View All →
                            </a>
                        @endif
                    </div>
                    
                    @if($bank->bankStatements->count() > 0)
                        <div class="space-y-3">
                            @foreach($bank->bankStatements as $statement)
                                <div class="flex items-center space-x-4 p-4 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition group">
                                    <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center group-hover:bg-blue-600/30 transition">
                                        <i class="fas fa-file-pdf text-blue-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-white font-semibold text-sm truncate">{{ $statement->original_filename }}</p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-gray-400 text-xs">
                                                <i class="fas fa-calendar mr-1"></i>
                                                {{ $statement->statement_period_start?->format('M Y') ?? '-' }}
                                            </span>
                                            <span class="text-gray-400 text-xs">
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $statement->uploaded_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center space-x-1 px-3 py-1 rounded-lg text-xs font-semibold
                                            {{ $statement->ocr_status === 'completed' ? 'bg-green-600/20 text-green-400' : '' }}
                                            {{ $statement->ocr_status === 'processing' ? 'bg-yellow-600/20 text-yellow-400' : '' }}
                                            {{ $statement->ocr_status === 'failed' ? 'bg-red-600/20 text-red-400' : '' }}
                                            {{ $statement->ocr_status === 'pending' ? 'bg-blue-600/20 text-blue-400' : '' }}">
                                            <i class="fas fa-circle text-xs"></i>
                                            <span>{{ ucfirst($statement->ocr_status) }}</span>
                                        </span>
                                        <a href="{{ route('bank-statements.show', $statement) }}" class="p-2 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg mb-4">No bank statements uploaded yet</p>
                            <a href="{{ route('bank-statements.create') }}?bank={{ $bank->id }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                <i class="fas fa-upload"></i>
                                <span>Upload Statement</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Bank Information -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                        Bank Information
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-start justify-between py-3 border-b border-slate-700">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-barcode text-gray-400"></i>
                                <span class="text-gray-400 text-sm">Bank Code</span>
                            </div>
                            <span class="text-white font-semibold text-sm">{{ $bank->code }}</span>
                        </div>
                        <div class="flex items-start justify-between py-3 border-b border-slate-700">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-building text-gray-400"></i>
                                <span class="text-gray-400 text-sm">Bank Name</span>
                            </div>
                            <span class="text-white font-semibold text-sm text-right">{{ $bank->name }}</span>
                        </div>
                        <div class="flex items-start justify-between py-3 border-b border-slate-700">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-toggle-on text-gray-400"></i>
                                <span class="text-gray-400 text-sm">Status</span>
                            </div>
                            @if($bank->is_active)
                                <span class="inline-flex items-center space-x-1 px-3 py-1 bg-green-600/20 text-green-400 rounded-lg text-xs font-semibold">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Active</span>
                                </span>
                            @else
                                <span class="inline-flex items-center space-x-1 px-3 py-1 bg-red-600/20 text-red-400 rounded-lg text-xs font-semibold">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Inactive</span>
                                </span>
                            @endif
                        </div>
                        <div class="flex items-start justify-between py-3 border-b border-slate-700">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-plus text-gray-400"></i>
                                <span class="text-gray-400 text-sm">Created</span>
                            </div>
                            <div class="text-right">
                                <p class="text-white font-semibold text-sm">{{ $bank->created_at->format('M d, Y') }}</p>
                                <p class="text-gray-500 text-xs">{{ $bank->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-start justify-between py-3">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-clock text-gray-400"></i>
                                <span class="text-gray-400 text-sm">Last Updated</span>
                            </div>
                            <div class="text-right">
                                <p class="text-white font-semibold text-sm">{{ $bank->updated_at->format('M d, Y') }}</p>
                                <p class="text-gray-500 text-xs">{{ $bank->updated_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-bolt mr-2 text-purple-500"></i>
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="{{ route('bank-statements.create') }}?bank={{ $bank->id }}" class="block bg-blue-600 hover:bg-blue-700 rounded-xl p-4 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-upload text-white text-2xl mb-2"></i>
                            <p class="text-white text-sm font-semibold">Upload Statement</p>
                        </a>
                        <a href="{{ route('banks.edit', $bank) }}" class="block bg-purple-600 hover:bg-purple-700 rounded-xl p-4 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-edit text-white text-2xl mb-2"></i>
                            <p class="text-white text-sm font-semibold">Edit Bank</p>
                        </a>
                        <form action="{{ route('banks.toggle-active', $bank) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 rounded-xl p-4 text-center transition-all transform hover:scale-105">
                                <i class="fas fa-sync-alt text-white text-2xl mb-2"></i>
                                <p class="text-white text-sm font-semibold">Toggle Status</p>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Statistics Summary -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-green-500"></i>
                        Statistics
                    </h3>
                    <div class="space-y-4">
                        @php
                            $total = $stats['total_statements'];
                            $completedPercent = $total > 0 ? round(($stats['completed'] / $total) * 100) : 0;
                            $processingPercent = $total > 0 ? round(($stats['processing'] / $total) * 100) : 0;
                            $pendingPercent = $total > 0 ? round(($stats['pending'] / $total) * 100) : 0;
                            $failedPercent = $total > 0 ? round(($stats['failed'] / $total) * 100) : 0;
                        @endphp
                        
                        <!-- Completed -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-400 text-sm">Completed</span>
                                <span class="text-white font-semibold text-sm">{{ $completedPercent }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ $completedPercent }}%"></div>
                            </div>
                        </div>

                        <!-- Processing -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-400 text-sm">Processing</span>
                                <span class="text-white font-semibold text-sm">{{ $processingPercent }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full transition-all" style="width: {{ $processingPercent }}%"></div>
                            </div>
                        </div>

                        <!-- Pending -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-400 text-sm">Pending</span>
                                <span class="text-white font-semibold text-sm">{{ $pendingPercent }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ $pendingPercent }}%"></div>
                            </div>
                        </div>

                        <!-- Failed -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-400 text-sm">Failed</span>
                                <span class="text-white font-semibold text-sm">{{ $failedPercent }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full transition-all" style="width: {{ $failedPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>