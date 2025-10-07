<x-app-layout>
    <x-slot name="header">Bank Statements Management</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Bank Statements</h2>
                <p class="text-gray-400">Upload and manage your bank statements for automatic transaction processing</p>
            </div>
            <a href="{{ route('bank-statements.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-upload"></i>
                <span>Upload Statement</span>
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Statements</p>
                        <p class="text-white text-3xl font-bold">{{ $statements->total() }}</p>
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
                        <p class="text-white text-3xl font-bold">{{ $statements->where('ocr_status', 'processing')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-spinner text-white text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Completed</p>
                        <p class="text-white text-3xl font-bold">{{ $statements->where('ocr_status', 'completed')->count() }}</p>
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
                        <p class="text-white text-3xl font-bold">{{ $statements->where('ocr_status', 'failed')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statements List --}}
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">All Bank Statements</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($statements as $statement)
                        <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 flex-1">
                                    {{-- Bank Logo/Icon --}}
                                    <div class="w-16 h-16 bg-slate-700 rounded-xl flex items-center justify-center">
                                        @if($statement->bank->logo_url)
                                            <img src="{{ $statement->bank->logo_url }}" alt="{{ $statement->bank->name }}" class="w-12 h-12 object-contain">
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
                                            
                                            <span class="px-3 py-1 bg-{{ $status['color'] }}-600/20 text-{{ $status['color'] }}-400 rounded-lg text-xs font-semibold">
                                                <i class="fas fa-{{ $status['icon'] }} mr-1 {{ $statement->ocr_status === 'processing' ? 'fa-spin' : '' }}"></i>
                                                {{ $status['text'] }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-400">
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
                                            @if($statement->transactions_count > 0)
                                                <span>
                                                    <i class="fas fa-list mr-1"></i>
                                                    {{ $statement->transactions_count }} transactions
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('bank-statements.show', $statement) }}" class="p-3 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($statement->ocr_status === 'completed')
                                        <form action="{{ route('bank-statements.process-matching', $statement) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-3 bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded-lg transition-all" title="Process Matching">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <a href="{{ route('bank-statements.download', $statement) }}" class="p-3 bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white rounded-lg transition-all" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button onclick="confirmDelete({{ $statement->id }}, '{{ $statement->original_filename }}')" class="p-3 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $statement->id }}" action="{{ route('bank-statements.destroy', $statement) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-file-pdf text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400 text-lg mb-2">No bank statements found</p>
                            <p class="text-gray-500 text-sm mb-6">Upload your first bank statement to get started</p>
                            <a href="{{ route('bank-statements.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                <i class="fas fa-upload"></i>
                                <span>Upload First Statement</span>
                            </a>
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

    <script>
        let deleteFormId = null;

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

        // Auto refresh for processing statements
        const hasProcessing = {{ $statements->where('ocr_status', 'processing')->count() > 0 ? 'true' : 'false' }};
        if (hasProcessing) {
            setTimeout(() => {
                window.location.reload();
            }, 10000); // Refresh every 10 seconds
        }
    </script>
</x-app-layout>