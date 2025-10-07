<x-app-layout>
    <x-slot name="header">
        Banks Management
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 space-y-4 sm:space-y-0">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Manage Banks</h2>
                <p class="text-gray-400">Manage supported banks for statement processing</p>
            </div>
            <a href="{{ route('banks.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add New Bank</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Banks</p>
                        <p class="text-white text-3xl font-bold">{{ $banks->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-university text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Active Banks</p>
                        <p class="text-white text-3xl font-bold">{{ \App\Models\Bank::active()->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Statements</p>
                        <p class="text-white text-3xl font-bold">{{ $banks->sum('bank_statements_count') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($banks as $bank)
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden hover:scale-105 transition-transform">
                    
                    <!-- Bank Header -->
                    <div class="p-6 border-b border-slate-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                @if($bank->logo)
                                    <img src="{{ Storage::url($bank->logo) }}" alt="{{ $bank->name }}" class="w-16 h-16 object-contain rounded-lg bg-white p-2">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-university text-white text-2xl"></i>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="text-xl font-bold text-white">{{ $bank->name }}</h3>
                                    <p class="text-gray-400 text-sm">{{ $bank->code }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <form action="{{ route('banks.toggle-active', $bank) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button 
                                type="submit"
                                class="inline-flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all
                                    {{ $bank->is_active 
                                        ? 'bg-green-600/20 text-green-400 hover:bg-green-600/30' 
                                        : 'bg-red-600/20 text-red-400 hover:bg-red-600/30' }}">
                                <i class="fas {{ $bank->is_active ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                <span>{{ $bank->is_active ? 'Active' : 'Inactive' }}</span>
                            </button>
                        </form>
                    </div>

                    <!-- Bank Stats -->
                    <div class="p-6 bg-slate-900/50">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center">
                                <p class="text-gray-400 text-xs mb-1">Statements</p>
                                <p class="text-white text-2xl font-bold">{{ $bank->bank_statements_count }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-400 text-xs mb-1">Created</p>
                                <p class="text-white text-sm font-semibold">{{ $bank->created_at->format('M Y') }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <a href="{{ route('banks.show', $bank) }}" class="flex-1 bg-teal-600/20 hover:bg-teal-600 text-teal-400 hover:text-white px-4 py-2 rounded-lg text-center transition-all text-sm font-semibold">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            <a href="{{ route('banks.edit', $bank) }}" class="flex-1 bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white px-4 py-2 rounded-lg text-center transition-all text-sm font-semibold">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <button 
                                onclick="confirmDelete({{ $bank->id }}, '{{ $bank->name }}', {{ $bank->bank_statements_count }})"
                                class="flex-1 bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white px-4 py-2 rounded-lg text-center transition-all text-sm font-semibold">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                            <form id="delete-form-{{ $bank->id }}" action="{{ route('banks.destroy', $bank) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-12 text-center border border-slate-700">
                        <i class="fas fa-university text-gray-600 text-6xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">No Banks Found</h3>
                        <p class="text-gray-400 mb-6">Get started by adding your first bank</p>
                        <a href="{{ route('banks.create') }}" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                            <i class="fas fa-plus"></i>
                            <span>Add Bank</span>
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($banks->hasPages())
            <div class="mt-8">
                {{ $banks->links() }}
            </div>
        @endif

    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
            
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Bank?</h3>
                    <p class="text-gray-400 mb-4">Are you sure you want to delete <strong id="deleteBankName" class="text-white"></strong>?</p>
                    <p id="statementWarning" class="text-yellow-400 text-sm hidden">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        This bank has <span id="statementCount"></span> statements and cannot be deleted.
                    </p>
                </div>

                <div class="flex space-x-3" id="deleteActions">
                    <button 
                        type="button"
                        onclick="closeDeleteModal()"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                    <button 
                        type="button"
                        id="confirmDeleteBtn"
                        onclick="submitDelete()"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Delete Bank
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteFormId = null;

        function confirmDelete(bankId, bankName, statementCount) {
            deleteFormId = bankId;
            document.getElementById('deleteBankName').textContent = bankName;
            
            if (statementCount > 0) {
                document.getElementById('statementCount').textContent = statementCount;
                document.getElementById('statementWarning').classList.remove('hidden');
                document.getElementById('confirmDeleteBtn').disabled = true;
                document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('statementWarning').classList.add('hidden');
                document.getElementById('confirmDeleteBtn').disabled = false;
                document.getElementById('confirmDeleteBtn').classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteFormId = null;
        }

        function submitDelete() {
            if (deleteFormId && !document.getElementById('confirmDeleteBtn').disabled) {
                document.getElementById('delete-form-' + deleteFormId).submit();
            }
        }
    </script>
</x-app-layout>