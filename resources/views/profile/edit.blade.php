<x-app-layout>
    <x-slot name="header">
        Profile Settings
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Profile Header Card -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl">
            <div class="flex items-center space-x-6">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <span class="text-white text-4xl font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">{{ auth()->user()->name }}</h2>
                    <p class="text-blue-100">{{ auth()->user()->email }}</p>
                    <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-white text-sm font-semibold">
                        <i class="fas fa-shield-alt mr-1"></i>{{ ucfirst(auth()->user()->role) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Update Profile Information -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-white mb-2">Profile Information</h3>
                        <p class="text-gray-400">Update your account's profile information and email address.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-user mr-2"></i>Name
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', auth()->user()->name) }}" 
                                required 
                                autofocus
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('name')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email', auth()->user()->email) }}" 
                                required
                                class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('email')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror

                            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                                <div class="mt-3 bg-yellow-500/10 border border-yellow-500/50 rounded-xl p-4">
                                    <p class="text-yellow-400 text-sm">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Your email address is unverified.
                                        <button form="send-verification" class="underline hover:text-yellow-300 transition">
                                            Click here to re-send the verification email.
                                        </button>
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between pt-4">
                            <button 
                                type="submit"
                                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                            
                            @if (session('status') === 'profile-updated')
                                <p class="text-green-500 text-sm animate-fade-in">
                                    <i class="fas fa-check-circle mr-1"></i>Saved successfully.
                                </p>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Update Password -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 shadow-xl">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-white mb-2">Update Password</h3>
                        <p class="text-gray-400">Ensure your account is using a long, random password to stay secure.</p>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-lock mr-2"></i>Current Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            </div>
                            @error('current_password')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">
                                <i class="fas fa-key mr-2"></i>New Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
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
                                <i class="fas fa-check-double mr-2"></i>Confirm Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            </div>
                            @error('password_confirmation')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between pt-4">
                            <button 
                                type="submit"
                                class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg">
                                <i class="fas fa-save mr-2"></i>Update Password
                            </button>
                            
                            @if (session('status') === 'password-updated')
                                <p class="text-green-500 text-sm animate-fade-in">
                                    <i class="fas fa-check-circle mr-1"></i>Password updated.
                                </p>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Delete Account -->
                <div class="bg-gradient-to-br from-red-900/20 to-slate-900 rounded-2xl p-8 border border-red-500/30 shadow-xl">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-red-400 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
                        </h3>
                        <p class="text-gray-400">Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.</p>
                    </div>

                    <button 
                        type="button"
                        onclick="document.getElementById('deleteModal').classList.remove('hidden')"
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg">
                        <i class="fas fa-trash mr-2"></i>Delete Account
                    </button>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                
                <!-- Account Info -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Account Information</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Role</span>
                            <span class="text-white font-semibold">{{ ucfirst(auth()->user()->role) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Member Since</span>
                            <span class="text-white font-semibold">{{ auth()->user()->created_at->format('M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Email Status</span>
                            @if(auth()->user()->email_verified_at)
                                <span class="text-green-500 font-semibold">
                                    <i class="fas fa-check-circle"></i> Verified
                                </span>
                            @else
                                <span class="text-yellow-500 font-semibold">
                                    <i class="fas fa-exclamation-circle"></i> Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Activity Stats -->
                @if(auth()->user()->isAdmin())
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-4">Your Activity</h3>
                        <div class="space-y-4">
                            <div class="bg-slate-900/50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-400 text-sm">Uploaded Statements</span>
                                    <i class="fas fa-file-invoice text-blue-500"></i>
                                </div>
                                <p class="text-white text-2xl font-bold">{{ auth()->user()->bankStatements()->count() }}</p>
                            </div>
                            <div class="bg-slate-900/50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-400 text-sm">Verified Transactions</span>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <p class="text-white text-2xl font-bold">{{ auth()->user()->verifiedTransactions()->count() }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Quick Links -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                    <h3 class="text-lg font-bold text-white mb-4">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="{{ route('dashboard') }}" class="block px-4 py-3 bg-slate-900/50 rounded-xl text-gray-300 hover:bg-slate-700 hover:text-white transition">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 bg-slate-900/50 rounded-xl text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                <i class="fas fa-users-cog mr-2"></i>User Management
                            </a>
                        @endif
                        <a href="{{ route('transactions.index') }}" class="block px-4 py-3 bg-slate-900/50 rounded-xl text-gray-300 hover:bg-slate-700 hover:text-white transition">
                            <i class="fas fa-exchange-alt mr-2"></i>Transactions
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="document.getElementById('deleteModal').classList.add('hidden')"></div>
            
            <!-- Modal -->
            <div class="relative bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-700">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Delete Account?</h3>
                    <p class="text-gray-400">Are you sure you want to delete your account? This action cannot be undone. All of your data will be permanently removed.</p>
                </div>

                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="delete_password" class="block text-sm font-semibold text-gray-300 mb-2">
                            Confirm your password
                        </label>
                        <input 
                            type="password" 
                            id="delete_password" 
                            name="password" 
                            required
                            placeholder="Enter your password"
                            class="w-full px-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all">
                        @error('password')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex space-x-3">
                        <button 
                            type="button"
                            onclick="document.getElementById('deleteModal').classList.add('hidden')"
                            class="flex-1 bg-slate-700 hover:bg-slate-600 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all">
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Verification Form (Hidden) -->
    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">
            @csrf
        </form>
    @endif

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</x-app-layout>