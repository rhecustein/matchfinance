<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' • ' : '' }}{{ config('app.name', 'MatchFinance') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <style>
        body {
            background-color: #0f172a;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
                linear-gradient(to right, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
            background-size: 3rem 3rem;
            font-family: 'Poppins', sans-serif;
        }

        .navbar-blur {
            backdrop-filter: blur(12px);
            background-color: rgba(15, 23, 42, 0.8);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .dropdown-menu {
            transform-origin: top;
            animation: dropdownOpen 0.2s ease-out;
        }

        @keyframes dropdownOpen {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .mobile-menu {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .notification-badge::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #ef4444;
            animation: pulse-ring 1.5s infinite;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased" x-data="{ mobileMenu: false }">

    <!-- Top Navigation Bar -->
    <nav class="navbar-blur fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                
                <!-- Left: Logo & Nav Links -->
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg transition-transform group-hover:scale-110">
                            <i class="fas fa-chart-line text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold text-white hidden sm:block">MatchFinance</span>
                    </a>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>

                        @if(auth()->user()->isAdmin())
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="nav-link px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center">
                                    <i class="fas fa-database mr-2"></i>Master Data
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" x-transition class="dropdown-menu absolute left-0 mt-2 w-48 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                                    <a href="{{ route('banks.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-university mr-2 text-blue-500"></i>Banks
                                    </a>
                                    <a href="{{ route('types.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-tags mr-2 text-purple-500"></i>Types
                                    </a>
                                    <a href="{{ route('categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-folder mr-2 text-pink-500"></i>Categories
                                    </a>
                                    <a href="{{ route('sub-categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-folder-open mr-2 text-teal-500"></i>Sub Categories
                                    </a>
                                    <a href="{{ route('keywords.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-key mr-2 text-yellow-500"></i>Keywords
                                    </a>
                                </div>
                            </div>

                            <a href="{{ route('bank-statements.index') }}" class="nav-link {{ request()->routeIs('bank-statements.*') ? 'active' : '' }} px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                                <i class="fas fa-file-invoice mr-2"></i>Statements
                            </a>
                        @endif

                        <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }} px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                            <i class="fas fa-exchange-alt mr-2"></i>Transactions
                        </a>
                    </div>
                </div>

                <!-- Right: Actions & Profile -->
                <div class="flex items-center space-x-4">
                    
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('bank-statements.create') }}" class="hidden md:flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-lg">
                            <i class="fas fa-upload"></i>
                            <span>Upload</span>
                        </a>
                    @endif

                    <!-- User Profile -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs hidden lg:block"></i>
                        </button>

                        <div x-show="open" @click.away="open = false" x-transition class="dropdown-menu absolute right-0 mt-2 w-56 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-700">
                                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                    <i class="fas fa-users-cog mr-2"></i>User Management
                                </a>
                            @endif
                            
                            <div class="border-t border-slate-700"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300 transition">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-400 hover:text-white transition">
                        <i class="fas" :class="mobileMenu ? 'fa-times' : 'fa-bars'" class="text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenu" x-transition class="mobile-menu md:hidden bg-slate-900 border-t border-slate-700">
            <div class="px-4 py-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                
                @if(auth()->user()->isAdmin())
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Master Data</div>
                    <a href="{{ route('banks.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-university mr-2"></i>Banks
                    </a>
                    <a href="{{ route('types.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-tags mr-2"></i>Types
                    </a>
                    <a href="{{ route('categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-folder mr-2"></i>Categories
                    </a>
                    <a href="{{ route('sub-categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-folder-open mr-2"></i>Sub Categories
                    </a>
                    <a href="{{ route('keywords.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-key mr-2"></i>Keywords
                    </a>
                    <a href="{{ route('bank-statements.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-file-invoice mr-2"></i>Statements
                    </a>
                @endif
                
                <a href="{{ route('transactions.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-exchange-alt mr-2"></i>Transactions
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        @isset($header)
            <header class="bg-gradient-to-r from-slate-800 to-slate-900 border-b border-slate-700">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-bold text-white">{{ $header }}</h1>
                </div>
            </header>
        @endisset

        <!-- Alerts -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <p class="text-green-400 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    <p class="text-red-400 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-yellow-500/10 border border-yellow-500/50 rounded-xl p-4 flex items-center space-x-3">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                    <p class="text-yellow-400 font-medium">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        <div class="py-8">
            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} MatchFinance. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition">Privacy</a>
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition">Terms</a>
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>