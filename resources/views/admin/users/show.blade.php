<x-app-layout>
    <x-slot name="header">
        User Details
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-users mr-1"></i>Users
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">{{ $user->name }}</span>
            </nav>
        </div>

        <!-- User Profile Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <span class="text-white text-4xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-white mb-2">{{ $user->name }}</h2>
                    <p class="text-blue-100 mb-4">{{ $user->email }}</p>
                    <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                        <span class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-full text-white font-semibold backdrop-blur-sm">
                            <i class="fas {{ $user->role === 'admin' ? 'fa-user-shield' : 'fa-user' }}"></i>
                            <span>{{ ucfirst($user->role) }}</span>
                        </span>
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-green-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-2 px-4 py-2 bg-yellow-500/30 rounded-full text-white font-semibold backdrop-blur-sm">
                                <i class="fas fa-clock"></i>
                                <span>Unverified</span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                        <i class="fas fa-edit"></i>
                        <span>Edit User</span>
                    </a>
                    @if($user->id !== auth()->id())
                        <button onclick="confirmDelete()" class="bg-red-500/30 hover:bg-red-500/50 text-white px-6 py-3 rounded-xl font-semibold transition-all backdrop-blur-sm flex items-center space-x-2">
                            <i class="fas fa-trash"></i>
                            <span>Delete</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Member Since</p>
                <p class="text-white text-xl font-bold">{{ $user->created_at->format('M d, Y') }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $user->created_at->diffForHumans() }}</p>
            </div>

            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                </div>
                <p class="text-gray-400 text-sm mb-1">Last Updated</p>
                <p class="text-white text-xl font-bold">{{ $user->updated_at->format('M d, Y') }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ $user->updated_at->diffForHumans() }}</p>
            </div>

            @if($user->isAdmin())
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-file-invoice text-white text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mb-1">Bank Statements</p>
                    <p class="text-white text-2xl font-bold">{{ $user->bankStatements()->count() }}</p>
                    <p class="text-gray-500 text-xs mt-1">Total uploaded</p>
                </div>

                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mb-1">Verified Transactions</p>
                    <p class="text-white text-2xl font-bold">{{ $user->verifiedTransactions()->count() }}</p>
                    <p class="text-gray-500 text-xs mt-1">Total verified</p>
                </div>
            @else
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-teal-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-user-tag text-white text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mb-1">Account Type</p>
                    <p class="text-white text-xl font-bold">Regular User</p>
                    <p class="text-gray-500 text-xs mt-1">Limited access</p>
                </div>

                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mb-1">Permissions</p>
                    <p class="text-white text-xl font-bold">Read Only</p>
                    <p class="text-gray-500 text-xs mt-1">View transactions</p>
                </div>
            @endif

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Account Information -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-3 text-blue-500"></i>
                    Account Information
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start justify-between py-3 border-b border-slate-700">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-user text-gray-400"></i>
                            <span class="text-gray-400">Full Name</span>
                        </div>
                        <span class="text-white font-semibold">{{ $user->name }}</span>
                    </div>
                    <div class="flex items-start justify-between py-3 border-b border-slate-700">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-gray-400"></i>
                            <span class="text-gray-400">Email</span>
                        </div>
                        <span class="text-white font-semibold">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-start justify-between py-3 border-b border-slate-700">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-shield-alt text-gray-400"></i>
                            <span class="text-gray-400">Role</span>
                        </div>
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg text-sm font-semibold
                            {{ $user->role === 'admin' ? 'bg-purple-600/20 text-purple-400' : 'bg-blue-600/20 text-blue-400' }}">
                            <i class="fas {{ $user->role === 'admin' ? 'fa-user-shield' : 'fa-user' }}"></i>
                            <span>{{ ucfirst($user->role) }}</span>
                        </span>
                    </div>
                    <div class="flex items-start justify-between py-3 border-b border-slate-700">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-gray-400"></i>
                            <span class="text-gray-400">Email Status</span>
                        </div>
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center space-x-2 px-3 py-1 bg-green-600/20 text-green-400 rounded-lg text-sm font-semibold">
                                <i class="fas fa-check-circle"></i>
                                <span>Verified</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-2 px-3 py-1 bg-yellow-600/20 text-yellow-400 rounded-lg text-sm font-semibold">
                                <i class="fas fa-clock"></i>
                                <span>Unverified</span>
                            </span>
                        @endif
                    </div>
                    <div class="flex items-start justify-between py-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-calendar-plus text-gray-400"></i>
                            <span class="text-gray-400">Joined Date</span>
                        </div>
                        <div class="text-right">
                            <p class="text-white font-semibold">{{ $user->created_at->format('M d, Y') }}</p>
                            <p class="text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity & Permissions -->
            <div class="space-y-6">
                
                <!-- Recent Activity (Admin only) -->
                @if($user->isAdmin())
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                        <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                            <i class="fas fa-chart-line mr-3 text-green-500"></i>
                            Recent Activity
                        </h3>
                        <div class="space-y-4">
                            @php
                                $recentStatements = $user->getRecentBankStatements(3);
                            @endphp
                            @forelse($recentStatements as $statement)
                                <div class="flex items-center space-x-3 p-3 bg-slate-900/50 rounded-xl hover:bg-slate-800 transition">
                                    <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-invoice text-blue-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-white text-sm font-semibold">{{ $statement->bank->name }}</p>
                                        <p class="text-gray-400 text-xs">{{ $statement->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-400 text-sm text-center py-4">No recent activity</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Permissions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-key mr-3 text-yellow-500"></i>
                        Permissions
                    </h3>
                    <div class="space-y-3">
                        @if($user->isAdmin())
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">Manage Master Data</span>
                            </div>
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">Upload Bank Statements</span>
                            </div>
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">Verify Transactions</span>
                            </div>
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">User Management</span>
                            </div>
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">Full System Access</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">View Transactions</span>
                            </div>
                            <div class="flex items-center space-x-3 text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm">Edit Profile</span>
                            </div>
                            <div class="flex items-center space-x-3 text-red-400">
                                <i class="fas fa-times-circle"></i>
                                <span class="text-sm">Upload Bank Statements</span>
                            </div>
                            <div class="flex items-center space-x-3 text-red-400">
                                <i class="fas fa-times-circle"></i>
                                <span class="text-sm">Verify Transactions</span>
                            </div>
                            <div class="flex items-center space-x-3 text-red-400">
                                <i class="fas fa-times-circle"></i>
                                <span class="text-sm">Manage Master Data</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-bolt mr-3 text-purple-500"></i>
                        Quick Actions
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="bg-blue-600 hover:bg-blue-700 rounded-xl p-4 text-center transition-all transform hover:scale-105">
                            <i class="fas fa-edit text-white text-2xl mb-2"></i>
                            <p class="text-white text-sm font-semibold">Edit User</p>
                        </a>
                        <form action="{{ route('admin.users.toggle-role', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to change this user\'s role?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" @if($user->id === auth()->id()) disabled @endif class="w-full bg-purple-600 hover:bg-purple-700 rounded-xl p-4 text-center transition-all transform hover:scale-105 {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <i class="fas fa-sync-alt text-white text-2xl mb-2"></i>
                                <p class="text-white text-sm font-semibold">Toggle Role</p>
                            </button>
                        </form>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Delete Confirmation Modal -->
    @if($user->id !== auth()->id())
        <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
                
                <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Delete User?</h3>
                        <p class="text-gray-400">Are you sure you want to delete <strong class="text-white">{{ $user->name }}</strong>? This action cannot be undone.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                        @csrf
                        @method('DELETE')
                        <div class="flex space-x-3">
                            <button 
                                type="button"
                                onclick="closeDeleteModal()"
                                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                                Delete User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</x-app-layout>