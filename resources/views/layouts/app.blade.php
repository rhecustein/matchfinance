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
            background-color: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
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
            width: 80%;
        }

        .dropdown-menu {
            transform-origin: top;
            animation: dropdownOpen 0.2s ease-out;
        }

        @keyframes dropdownOpen {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mobile-menu {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        html {
            scroll-behavior: smooth;
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-animate {
            animation: slideInDown 0.3s ease-out;
        }

        /* Chat Widget Styles */
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .animate-bounce {
            animation: bounce 1s infinite;
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased min-h-screen flex flex-col" x-data="{ mobileMenu: false }">

    <!-- Top Navigation Bar -->
    <nav class="navbar-blur fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                
                <!-- Left: Logo & Main Navigation -->
                <div class="flex items-center space-x-6">
                    {{-- Logo --}}
                    <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg transition-transform group-hover:scale-110">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <span class="text-lg font-bold text-white hidden sm:block">MatchFinance</span>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex items-center space-x-1">
                        
                        @if(auth()->user()->isSuperAdmin())
                            {{-- Super Admin Menu --}}
                            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('admin.companies.index') }}" class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-building"></i>
                                <span>Companies</span>
                            </a>

                            <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-box"></i>
                                <span>Plans</span>
                            </a>

                            <a href="{{ route('admin.subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-credit-card"></i>
                                <span>Subscriptions</span>
                            </a>

                            <a href="{{ route('admin.system-users.index') }}" class="nav-link {{ request()->routeIs('admin.system-users.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-users-cog"></i>
                                <span>Users</span>
                            </a>
                        @else
                            {{-- Regular User Menu (Company Members) --}}
                            
                            {{-- Dashboard --}}
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-home"></i>
                                <span>Dashboard</span>
                            </a>

                            {{-- Statements --}}
                            <a href="{{ route('bank-statements.index') }}" class="nav-link {{ request()->routeIs('bank-statements.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-file-invoice"></i>
                                <span>Statements</span>
                            </a>

                            {{-- Transactions --}}
                            <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Transactions</span>
                            </a>

                            {{-- Reports (Manager+) --}}
                            @if(auth()->user()->hasManagementAccess())
                                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Reports</span>
                                </a>
                            @endif

                            {{-- Collections --}}
                            <a href="{{ route('document-collections.index') }}" class="nav-link {{ request()->routeIs('document-collections.*') ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                <i class="fas fa-folder-tree"></i>
                                <span>Collections</span>
                            </a>

                            {{-- Master Data Dropdown (Admin+) --}}
                            @if(auth()->user()->hasAdminAccess())
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="nav-link {{ request()->routeIs(['banks.*', 'types.*', 'categories.*', 'sub-categories.*', 'keywords.*', 'accounts.*']) ? 'active' : '' }} px-3 py-2 text-sm font-medium text-gray-300 hover:text-white transition flex items-center space-x-2">
                                        <i class="fas fa-database"></i>
                                        <span>Master Data</span>
                                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
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
                                        <div class="px-4 py-2 bg-slate-900/50 border-b border-slate-700">
                                            <p class="text-xs font-semibold text-gray-400 uppercase">Category Hierarchy</p>
                                        </div>
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
                                        <div class="border-t border-slate-700"></div>
                                        <div class="px-4 py-2 bg-slate-900/50">
                                            <p class="text-xs font-semibold text-gray-400 uppercase">Accounting</p>
                                        </div>
                                        <a href="{{ route('accounts.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                            <i class="fas fa-wallet mr-2 text-indigo-500 w-5"></i>Accounts
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Right: Actions & Profile -->
                <div class="flex items-center space-x-3">
                    
                    {{-- Quick Actions (Admin Only) --}}
                    @if(auth()->user()->hasAdminAccess() && !auth()->user()->isSuperAdmin())
                        <a href="{{ route('bank-statements.create') }}" class="hidden lg:flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-lg hover:shadow-xl">
                            <i class="fas fa-upload"></i>
                            <span>Upload</span>
                        </a>

                        {{-- Admin Dropdown --}}
                        <div class="hidden lg:block relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition">
                                <i class="fas fa-user-shield"></i>
                                <span class="text-sm font-medium">Admin</span>
                                <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                            </button>
                            
                            <div x-show="open" 
                                 @click.away="open = false" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="dropdown-menu absolute right-0 mt-2 w-48 bg-slate-800 rounded-xl shadow-2xl border border-slate-700 overflow-hidden">
                                <a href="{{ route('users.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                    <i class="fas fa-users mr-2 text-blue-500 w-5"></i>Users
                                </a>
                                <a href="{{ route('settings.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                    <i class="fas fa-sliders-h mr-2 text-gray-500 w-5"></i>Settings
                                </a>
                                <div class="border-t border-slate-700"></div>
                                <form method="POST" action="{{ route('dashboard.clear-cache') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-trash mr-2 text-red-500 w-5"></i>Clear Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- User Profile -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center ring-2 ring-purple-500/20">
                                <span class="text-white text-sm font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-sm font-semibold text-white leading-none">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if(auth()->user()->isSuperAdmin())
                                        Super Admin
                                    @elseif(auth()->user()->isOwner())
                                        Owner
                                    @elseif(auth()->user()->isAdmin())
                                        Admin
                                    @elseif(auth()->user()->isManager())
                                        Manager
                                    @else
                                        {{ ucfirst(auth()->user()->role) }}
                                    @endif
                                </p>
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
                                @if(auth()->user()->company)
                                    <p class="text-xs text-purple-400 mt-1 font-medium flex items-center">
                                        <i class="fas fa-building mr-1.5"></i>{{ auth()->user()->company->name }}
                                    </p>
                                @endif
                            </div>
                            
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                    <i class="fas fa-user mr-3 text-blue-500 w-5"></i>Profile
                                </a>
                                
                                @if(auth()->user()->isSuperAdmin())
                                    <div class="border-t border-slate-700 mt-1"></div>
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition">
                                        <i class="fas fa-tachometer-alt mr-3 text-red-500 w-5"></i>Admin Panel
                                    </a>
                                @endif

                                @if(auth()->user()->hasAdminAccess() && !auth()->user()->isSuperAdmin())
                                    <div class="border-t border-slate-700 mt-1 lg:hidden"></div>
                                    <a href="{{ route('users.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition lg:hidden">
                                        <i class="fas fa-users-cog mr-3 text-blue-500 w-5"></i>Manage Users
                                    </a>
                                    <a href="{{ route('settings.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition lg:hidden">
                                        <i class="fas fa-cog mr-3 text-gray-500 w-5"></i>Settings
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
                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
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
             class="mobile-menu lg:hidden bg-slate-900 border-t border-slate-700">
            <div class="px-4 py-3 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto">
                
                @if(auth()->user()->isSuperAdmin())
                    {{-- Super Admin Mobile --}}
                    <div class="pb-2 px-4">
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Super Admin</p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-home mr-2 w-5"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.companies.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('admin.companies.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-building mr-2 w-5"></i>Companies
                    </a>
                    <a href="{{ route('admin.plans.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('admin.plans.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-box mr-2 w-5"></i>Plans
                    </a>
                    <a href="{{ route('admin.subscriptions.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('admin.subscriptions.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-credit-card mr-2 w-5"></i>Subscriptions
                    </a>
                    <a href="{{ route('admin.system-users.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('admin.system-users.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-users-cog mr-2 w-5"></i>Users
                    </a>
                @else
                    {{-- Company User Mobile --}}
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-home mr-2 w-5"></i>Dashboard
                    </a>
                    <a href="{{ route('bank-statements.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('bank-statements.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-file-invoice mr-2 w-5"></i>Statements
                    </a>
                    <a href="{{ route('transactions.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('transactions.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-exchange-alt mr-2 w-5"></i>Transactions
                    </a>

                    @if(auth()->user()->hasManagementAccess())
                        <a href="{{ route('reports.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('reports.*') ? 'bg-slate-800 text-white' : '' }}">
                            <i class="fas fa-chart-bar mr-2 w-5"></i>Reports
                        </a>
                    @endif

                    <a href="{{ route('document-collections.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition {{ request()->routeIs('document-collections.*') ? 'bg-slate-800 text-white' : '' }}">
                        <i class="fas fa-folder-tree mr-2 w-5"></i>Collections
                    </a>
                    
                    @if(auth()->user()->hasAdminAccess())
                        <div class="pt-4 pb-2 px-4 border-t border-slate-700 mt-2">
                            <p class="text-xs font-semibold text-blue-400 uppercase tracking-wider">Master Data</p>
                        </div>
                        <a href="{{ route('banks.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-university mr-2 w-5"></i>Banks
                        </a>
                        <a href="{{ route('types.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-tags mr-2 w-5"></i>Types
                        </a>
                        <a href="{{ route('categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-folder mr-2 w-5"></i>Categories
                        </a>
                        <a href="{{ route('sub-categories.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-folder-open mr-2 w-5"></i>Sub Categories
                        </a>
                        <a href="{{ route('keywords.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-key mr-2 w-5"></i>Keywords
                        </a>
                        <a href="{{ route('accounts.index') }}" class="block px-4 py-3 text-sm text-gray-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                            <i class="fas fa-wallet mr-2 w-5"></i>Accounts
                        </a>

                        <div class="pt-4">
                            <a href="{{ route('bank-statements.create') }}" class="block px-4 py-3 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition font-semibold text-center">
                                <i class="fas fa-upload mr-2"></i>Upload Statement
                            </a>
                        </div>
                    @endif
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

    {{-- AI Chat Widget - Only for Company Users --}}
    @if(!auth()->user()->isSuperAdmin())
        <x-ai-chat-widget />
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>