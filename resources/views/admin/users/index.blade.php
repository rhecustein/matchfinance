<x-app-layout>
    <x-slot name="header">
        User Management
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 space-y-4 sm:space-y-0">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2">Manage Users</h2>
                <p class="text-gray-400">Add, edit, or remove users from the system</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add New User</span>
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Total Users</p>
                        <p class="text-white text-3xl font-bold">{{ $users->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Admins</p>
                        <p class="text-white text-3xl font-bold">{{ \App\Models\User::admins()->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-shield text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Regular Users</p>
                        <p class="text-white text-3xl font-bold">{{ \App\Models\User::regularUsers()->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl border border-slate-700 shadow-xl overflow-hidden">
            
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-slate-700">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                    <h3 class="text-lg font-bold text-white">All Users</h3>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="Search users..."
                            class="bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 pl-10 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all w-full sm:w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    </div>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700" id="usersTableBody">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-800/50 transition" data-user-name="{{ strtolower($user->name) }}" data-user-email="{{ strtolower($user->email) }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-white font-semibold">{{ $user->name }}</p>
                                            <p class="text-gray-400 text-sm">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.users.toggle-role', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to change this user\'s role?')">
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit"
                                            @if($user->id === auth()->id()) disabled @endif
                                            class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all
                                                {{ $user->role === 'admin' 
                                                    ? 'bg-purple-600/20 text-purple-400 hover:bg-purple-600/30' 
                                                    : 'bg-blue-600/20 text-blue-400 hover:bg-blue-600/30' }}
                                                {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}">
                                            <i class="fas {{ $user->role === 'admin' ? 'fa-user-shield' : 'fa-user' }}"></i>
                                            <span>{{ ucfirst($user->role) }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center space-x-1 px-3 py-1.5 bg-green-600/20 text-green-400 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Verified</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center space-x-1 px-3 py-1.5 bg-yellow-600/20 text-yellow-400 rounded-lg text-sm font-semibold">
                                            <i class="fas fa-clock"></i>
                                            <span>Pending</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-gray-300 text-sm">{{ $user->created_at->format('M d, Y') }}</p>
                                    <p class="text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="p-2 bg-teal-600/20 text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg transition-all" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="p-2 bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg transition-all" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if($user->id !== auth()->id())
                                            <button 
                                                onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')"
                                                class="p-2 bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white rounded-lg transition-all" 
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @else
                                            <button 
                                                disabled
                                                class="p-2 bg-gray-600/20 text-gray-500 rounded-lg cursor-not-allowed" 
                                                title="Cannot delete yourself">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users text-gray-600 text-5xl mb-4"></i>
                                        <p class="text-gray-400 text-lg">No users found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-700">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

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
                    <h3 class="text-2xl font-bold text-white mb-2">Delete User?</h3>
                    <p class="text-gray-400">Are you sure you want to delete <strong id="deleteUserName" class="text-white"></strong>? This action cannot be undone.</p>
                </div>

                <div class="flex space-x-3">
                    <button 
                        type="button"
                        onclick="closeDeleteModal()"
                        class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Cancel
                    </button>
                    <button 
                        type="button"
                        onclick="submitDelete()"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteFormId = null;

        function confirmDelete(userId, userName) {
            deleteFormId = userId;
            document.getElementById('deleteUserName').textContent = userName;
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

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('usersTableBody');
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const name = row.dataset.userName || '';
                const email = row.dataset.userEmail || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</x-app-layout>