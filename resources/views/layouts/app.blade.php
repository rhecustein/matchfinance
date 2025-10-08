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

        /* Alert auto-dismiss animation */
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-animate {
            animation: slideInDown 0.3s ease-out;
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased min-h-screen flex flex-col" x-data="{ mobileMenu: false }">

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
                                    <i class="fas fa-chevron-down ml-1 text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                                </button>
                                
                                <div x-show="open" 
                                     @click.away="open = false" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95"
                                     class="dropdown-menu absolute left-0 mt-2 w-56 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                                    <a href="{{ route('banks.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-university mr-2 text-blue-500 w-5"></i>Banks
                                    </a>
                                    <a href="{{ route('types.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-tags mr-2 text-purple-500 w-5"></i>Types
                                    </a>
                                    <a href="{{ route('categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-folder mr-2 text-pink-500 w-5"></i>Categories
                                    </a>
                                    <a href="{{ route('sub-categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-folder-open mr-2 text-teal-500 w-5"></i>Sub Categories
                                    </a>
                                    <a href="{{ route('keywords.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-key mr-2 text-yellow-500 w-5"></i>Keywords
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

                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }} px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition">
                            <i class="fas fa-chart-line mr-2"></i>Reports
                        </a>
                    </div>
                </div>

                <!-- Right: Actions & Profile -->
                <div class="flex items-center space-x-4">
                    
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('bank-statements.create') }}" class="hidden md:flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-upload"></i>
                            <span>Upload Statement</span>
                        </a>
                    @endif

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center ring-2 ring-purple-500/20">
                                <span class="text-white text-sm font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs hidden lg:block transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>

                        <div x-show="open" 
                             @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="dropdown-menu absolute right-0 mt-2 w-64 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-700 bg-slate-900/50">
                                <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                    <i class="fas fa-user mr-3 text-blue-500 w-5"></i>Profile Settings
                                </a>
                                
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.users.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-users-cog mr-3 text-purple-500 w-5"></i>User Management
                                    </a>
                                @endif
                            </div>
                            
                            <div class="border-t border-slate-700"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300 transition">
                                    <i class="fas fa-sign-out-alt mr-3 w-5"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-400 hover:text-white transition">
                        <i class="fas text-xl" :class="mobileMenu ? 'fa-times' : 'fa-bars'"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenu" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-4"
             class="mobile-menu md:hidden bg-slate-900 border-t border-slate-700">
            <div class="px-4 py-3 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">
                    <i class="fas fa-home mr-2 w-5"></i>Dashboard
                </a>
                
                @if(auth()->user()->isAdmin())
                    <div class="pt-4 pb-2 px-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Master Data</p>
                    </div>
                    <a href="{{ route('banks.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-university mr-2 text-blue-500 w-5"></i>Banks
                    </a>
                    <a href="{{ route('types.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-tags mr-2 text-purple-500 w-5"></i>Types
                    </a>
                    <a href="{{ route('categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-folder mr-2 text-pink-500 w-5"></i>Categories
                    </a>
                    <a href="{{ route('sub-categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-folder-open mr-2 text-teal-500 w-5"></i>Sub Categories
                    </a>
                    <a href="{{ route('keywords.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-key mr-2 text-yellow-500 w-5"></i>Keywords
                    </a>
                    
                    <div class="pt-4 pb-2 px-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Transactions</p>
                    </div>
                    <a href="{{ route('bank-statements.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <i class="fas fa-file-invoice mr-2 text-green-500 w-5"></i>Statements
                    </a>
                @endif
                
                <a href="{{ route('transactions.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('transactions.*') ? 'bg-slate-800 text-white' : '' }}">
                    <i class="fas fa-exchange-alt mr-2 text-orange-500 w-5"></i>Transactions
                </a>

                <a href="{{ route('reports.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('reports.*') ? 'bg-slate-800 text-white' : '' }}">
                    <i class="fas fa-chart-line mr-2 text-indigo-500 w-5"></i>Reports
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-4">
                        <a href="{{ route('bank-statements.create') }}" class="block px-4 py-3 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition font-semibold text-center">
                            <i class="fas fa-upload mr-2"></i>Upload Statement
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16 flex-grow">
        @isset($header)
            <header class="bg-gradient-to-r from-slate-800 to-slate-900 border-b border-slate-700 shadow-lg">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-bold text-white">{{ $header }}</h1>
                </div>
            </header>
        @endisset

        <!-- Alerts Container -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="alert-animate mt-6 bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        <p class="text-green-400 font-medium">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="text-green-500 hover:text-green-400 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="alert-animate mt-6 bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        <p class="text-red-400 font-medium">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="text-red-500 hover:text-red-400 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('warning'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="alert-animate mt-6 bg-yellow-500/10 border border-yellow-500/50 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                        <p class="text-yellow-400 font-medium">{{ session('warning') }}</p>
                    </div>
                    <button @click="show = false" class="text-yellow-500 hover:text-yellow-400 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('info'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                     class="alert-animate mt-6 bg-blue-500/10 border border-blue-500/50 rounded-xl p-4 flex items-center justify-between shadow-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                        <p class="text-blue-400 font-medium">{{ session('info') }}</p>
                    </div>
                    <button @click="show = false" class="text-blue-500 hover:text-blue-400 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <div class="py-8">
            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm">MatchFinance</p>
                        <p class="text-gray-500 text-xs">&copy; {{ date('Y') }} All rights reserved</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition hover:underline">Privacy Policy</a>
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition hover:underline">Terms of Service</a>
                    <a href="#" class="text-gray-500 hover:text-gray-300 text-sm transition hover:underline">Contact Support</a>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-slate-800 text-center">
                <p class="text-gray-600 text-xs">Made with <i class="fas fa-heart text-red-500"></i> for better financial management</p>
            </div>
        </div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>