<x-app-layout>
    <x-slot name="header">
        Edit User
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-users mr-1"></i>Users
                </a>
                <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                <span class="text-white font-semibold">Edit User</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                <span class="text-white text-2xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Edit User</h2>
                                <p class="text-gray-400">Update user information</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-user mr-2"></i>Full Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', $user->name) }}" 
                                required 
                                autofocus
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            @error('name')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email Address
                                <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email', $user->email) }}" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            @error('email')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-shield-alt mr-2"></i>Role
                                <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="role" 
                                name="role" 
                                required
                                @if($user->id === auth()->id()) disabled @endif
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                            @if($user->id === auth()->id())
                                <p class="text-yellow-500 text-sm mt-2">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    You cannot change your own role
                                </p>
                            @endif
                        </div>

                        <div class="border-t border-slate-700 pt-6">
                            <p class="text-gray-400 text-sm mb-4">
                                <i class="fas fa-info-circle mr-2"></i>
                                Leave password fields empty to keep the current password
                            </p>

                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">
                                    <i class="fas fa-lock mr-2"></i>New Password
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all pr-12"
                                        placeholder="Leave blank to keep current">
                                    <button 
                                        type="button"
                                        onclick="togglePassword('password')"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="text-red-500 text-sm mt-2 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-300 mb-2">
                                    <i class="fas fa-check-double mr-2"></i>Confirm New Password
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="password_confirmation" 
                                        name="password_confirmation"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all pr-12"
                                        placeholder="Confirm new password">
                                    <button 
                                        type="button"
                                        onclick="togglePassword('password_confirmation')"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                        <i class="fas fa-eye" id="password_confirmation-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-6">
                            <button 
                                type="submit"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Update User</span>
                            </button>
                            <a 
                                href="{{ route('admin.users.index') }}"
                                class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all flex items-center justify-center space-x-2">
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- User Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">User Information</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Status</span>
                            @if($user->email_verified_at)
                                <span class="text-green-500 font-semibold">
                                    <i class="fas fa-check-circle"></i> Verified
                                </span>
                            @else
                                <span class="text-yellow-500 font-semibold">
                                    <i class="fas fa-clock"></i> Unverified
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Member Since</span>
                            <span class="text-white font-semibold">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Last Updated</span>
                            <span class="text-white font-semibold">{{ $user->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Activity Stats (if admin) -->
                @if($user->isAdmin())
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-4">Activity</h3>
                        <div class="space-y-4">
                            <div class="bg-slate-900/50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-400 text-sm">Statements</span>
                                    <i class="fas fa-file-invoice text-blue-500"></i>
                                </div>
                                <p class="text-white text-2xl font-bold">{{ $user->bankStatements()->count() }}</p>
                            </div>
                            <div class="bg-slate-900/50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-400 text-sm">Verified</span>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <p class="text-white text-2xl font-bold">{{ $user->verifiedTransactions()->count() }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Danger Zone -->
                @if($user->id !== auth()->id())
                    <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-6 border border-red-500/30 shadow-xl">
                        <h3 class="text-lg font-bold text-red-400 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                        </h3>
                        <p class="text-gray-400 text-sm mb-4">
                            Permanently delete this user account. This action cannot be undone.
                        </p>
                        <button 
                            type="button"
                            onclick="confirmDelete()"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-xl font-semibold transition-all shadow-lg">
                            <i class="fas fa-trash mr-2"></i>Delete User
                        </button>
                    </div>
                @endif

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

                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="space-y-4">
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
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</x-app-layout>