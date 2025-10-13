<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <!-- Dashboard -->
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <i class="fas fa-chart-line mr-2"></i>
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <!-- Bank Statements -->
                    <x-nav-link :href="route('bank-statements.index')" :active="request()->routeIs('bank-statements.*')">
                        <i class="fas fa-file-invoice mr-2"></i>
                        {{ __('Statements') }}
                    </x-nav-link>

                    <!-- Transactions -->
                    <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        {{ __('Transactions') }}
                    </x-nav-link>

                    <!-- AI Chat -->
                    <x-nav-link :href="route('chat-sessions.index')" :active="request()->routeIs('chat-sessions.*')">
                        <i class="fas fa-comments mr-2"></i>
                        {{ __('AI Chat') }}
                    </x-nav-link>

                    <!-- Document Collections -->
                    <x-nav-link :href="route('document-collections.index')" :active="request()->routeIs('document-collections.*')">
                        <i class="fas fa-folder-tree mr-2"></i>
                        {{ __('Collections') }}
                    </x-nav-link>

                    @if(auth()->user()->isAdmin())
                        <!-- Master Data Dropdown (Admin Only) -->
                        <div class="hidden sm:flex sm:items-center" x-data="{ masterOpen: false }" @click.away="masterOpen = false">
                            <button @click="masterOpen = !masterOpen" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                <i class="fas fa-database mr-2"></i>
                                {{ __('Master') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="masterOpen" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-right bg-white ring-1 ring-black ring-opacity-5"
                                 style="display: none; top: 4rem;">
                                <div class="rounded-md bg-white shadow-xs">
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase border-b">
                                        Category Hierarchy
                                    </div>
                                    <a href="{{ route('banks.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-university mr-2 text-blue-600"></i>{{ __('Banks') }}
                                    </a>
                                    <a href="{{ route('types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-tag mr-2 text-purple-600"></i>{{ __('Types') }}
                                    </a>
                                    <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-folder mr-2 text-yellow-600"></i>{{ __('Categories') }}
                                    </a>
                                    <a href="{{ route('sub-categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-folder-open mr-2 text-orange-600"></i>{{ __('Sub Categories') }}
                                    </a>
                                    <a href="{{ route('keywords.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-key mr-2 text-green-600"></i>{{ __('Keywords') }}
                                    </a>
                                    
                                    <div class="border-t border-gray-100"></div>
                                    
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">
                                        Accounting
                                    </div>
                                    <a href="{{ route('accounts.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-wallet mr-2 text-indigo-600"></i>{{ __('Accounts') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Reports -->
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            <i class="fas fa-chart-bar mr-2"></i>
                            {{ __('Reports') }}
                        </x-nav-link>

                        <!-- Admin Menu -->
                        <div class="hidden sm:flex sm:items-center" x-data="{ adminOpen: false }" @click.away="adminOpen = false">
                            <button @click="adminOpen = !adminOpen" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                <i class="fas fa-cog mr-2"></i>
                                {{ __('Admin') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="adminOpen" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-right bg-white ring-1 ring-black ring-opacity-5"
                                 style="display: none; top: 4rem;">
                                <div class="rounded-md bg-white shadow-xs">
                                    <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-users mr-2 text-blue-600"></i>{{ __('User Management') }}
                                    </a>
                                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sliders-h mr-2 text-gray-600"></i>{{ __('Settings') }}
                                    </a>
                                    
                                    <div class="border-t border-gray-100"></div>
                                    
                                    <form method="POST" action="{{ route('dashboard.clear-cache') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-trash mr-2 text-red-600"></i>{{ __('Clear Cache') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Side: Notifications & User Menu -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Quick Actions (Optional) -->
                <div class="flex items-center space-x-2 mr-4">
                    <!-- Upload Statement Button -->
                    <a href="{{ route('bank-statements.create') }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition ease-in-out duration-150"
                       title="Upload New Statement">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>
                        <span class="hidden lg:inline">Upload</span>
                    </a>
                    
                    <!-- New Chat Button -->
                    <a href="{{ route('chat-sessions.create') }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150"
                       title="Start New Chat">
                        <i class="fas fa-comment-medical mr-2"></i>
                        <span class="hidden lg:inline">New Chat</span>
                    </a>
                </div>

                <!-- Notifications -->
                <div class="relative mr-4">
                    <button class="relative text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fas fa-bell text-xl"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </button>
                </div>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold mr-2">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="text-left">
                                    <div class="font-medium text-sm">{{ Auth::user()->name }}</div>
                                    @if(auth()->user()->isSuperAdmin())
                                        <div class="text-xs text-red-600 font-semibold">Super Admin</div>
                                    @elseif(auth()->user()->isAdmin())
                                        <div class="text-xs text-blue-600 font-semibold">Admin</div>
                                    @else
                                        <div class="text-xs text-gray-500">User</div>
                                    @endif
                                </div>
                            </div>

                            <div class="ms-2">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Company Info (if needed) -->
                        @if(auth()->user()->company)
                            <div class="px-4 py-3 border-b border-gray-100">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Company</div>
                                <div class="text-sm font-medium text-gray-800">{{ auth()->user()->company->name }}</div>
                            </div>
                        @endif

                        <!-- Profile -->
                        <x-dropdown-link :href="route('profile.edit')">
                            <i class="fas fa-user mr-2"></i>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- My Chats -->
                        <x-dropdown-link :href="route('chat-sessions.index')">
                            <i class="fas fa-comments mr-2"></i>
                            {{ __('My AI Chats') }}
                        </x-dropdown-link>

                        <!-- My Collections -->
                        <x-dropdown-link :href="route('document-collections.index')">
                            <i class="fas fa-folder-tree mr-2"></i>
                            {{ __('My Collections') }}
                        </x-dropdown-link>

                        <!-- Super Admin Section -->
                        @if(auth()->user()->isSuperAdmin())
                            <div class="border-t border-gray-100"></div>
                            <div class="px-4 py-2 text-xs font-semibold text-red-600 uppercase">
                                Super Admin
                            </div>
                            
                            <x-dropdown-link :href="route('admin.dashboard')">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                {{ __('Admin Dashboard') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('admin.companies.index')">
                                <i class="fas fa-building mr-2"></i>
                                {{ __('Companies') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('admin.plans.index')">
                                <i class="fas fa-box mr-2"></i>
                                {{ __('Plans') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('admin.subscriptions.index')">
                                <i class="fas fa-credit-card mr-2"></i>
                                {{ __('Subscriptions') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('admin.system-users.index')">
                                <i class="fas fa-users-cog mr-2"></i>
                                {{ __('System Users') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('admin.settings.index')">
                                <i class="fas fa-server mr-2"></i>
                                {{ __('System Settings') }}
                            </x-dropdown-link>
                        @endif

                        <!-- Company Admin Section -->
                        @if(auth()->user()->isAdmin())
                            <div class="border-t border-gray-100"></div>
                            
                            <x-dropdown-link :href="route('users.index')">
                                <i class="fas fa-users mr-2"></i>
                                {{ __('User Management') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('banks.index')">
                                <i class="fas fa-university mr-2"></i>
                                {{ __('Manage Banks') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('settings.index')">
                                <i class="fas fa-cog mr-2"></i>
                                {{ __('Company Settings') }}
                            </x-dropdown-link>
                        @endif

                        <div class="border-t border-gray-100"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <!-- Dashboard -->
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <i class="fas fa-chart-line mr-2"></i>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <!-- Bank Statements -->
            <x-responsive-nav-link :href="route('bank-statements.index')" :active="request()->routeIs('bank-statements.*')">
                <i class="fas fa-file-invoice mr-2"></i>
                {{ __('Bank Statements') }}
            </x-responsive-nav-link>

            <!-- Transactions -->
            <x-responsive-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                <i class="fas fa-exchange-alt mr-2"></i>
                {{ __('Transactions') }}
            </x-responsive-nav-link>

            <!-- AI Chat -->
            <x-responsive-nav-link :href="route('chat-sessions.index')" :active="request()->routeIs('chat-sessions.*')">
                <i class="fas fa-comments mr-2"></i>
                {{ __('AI Chat') }}
            </x-responsive-nav-link>

            <!-- Document Collections -->
            <x-responsive-nav-link :href="route('document-collections.index')" :active="request()->routeIs('document-collections.*')">
                <i class="fas fa-folder-tree mr-2"></i>
                {{ __('Collections') }}
            </x-responsive-nav-link>

            @if(auth()->user()->isAdmin())
                <!-- Master Data Section -->
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">
                        Master Data
                    </div>
                    
                    <x-responsive-nav-link :href="route('banks.index')" :active="request()->routeIs('banks.*')">
                        <i class="fas fa-university mr-2"></i>
                        {{ __('Banks') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('types.index')" :active="request()->routeIs('types.*')">
                        <i class="fas fa-tag mr-2"></i>
                        {{ __('Types') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                        <i class="fas fa-folder mr-2"></i>
                        {{ __('Categories') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('sub-categories.index')" :active="request()->routeIs('sub-categories.*')">
                        <i class="fas fa-folder-open mr-2"></i>
                        {{ __('Sub Categories') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('keywords.index')" :active="request()->routeIs('keywords.*')">
                        <i class="fas fa-key mr-2"></i>
                        {{ __('Keywords') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                        <i class="fas fa-wallet mr-2"></i>
                        {{ __('Accounts') }}
                    </x-responsive-nav-link>
                </div>

                <!-- Reports -->
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    <i class="fas fa-chart-bar mr-2"></i>
                    {{ __('Reports') }}
                </x-responsive-nav-link>

                <!-- Admin Section -->
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">
                        Administration
                    </div>

                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        <i class="fas fa-users mr-2"></i>
                        {{ __('Users') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                        <i class="fas fa-cog mr-2"></i>
                        {{ __('Settings') }}
                    </x-responsive-nav-link>
                </div>
            @endif

            @if(auth()->user()->isSuperAdmin())
                <!-- Super Admin Section -->
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-red-500 uppercase">
                        Super Admin
                    </div>

                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        {{ __('Admin Dashboard') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">
                        <i class="fas fa-building mr-2"></i>
                        {{ __('Companies') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('admin.plans.index')" :active="request()->routeIs('admin.plans.*')">
                        <i class="fas fa-box mr-2"></i>
                        {{ __('Plans') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('admin.subscriptions.index')" :active="request()->routeIs('admin.subscriptions.*')">
                        <i class="fas fa-credit-card mr-2"></i>
                        {{ __('Subscriptions') }}
                    </x-responsive-nav-link>
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold mr-3">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                        @if(auth()->user()->isSuperAdmin())
                            <div class="text-xs text-red-600 font-semibold">Super Administrator</div>
                        @elseif(auth()->user()->isAdmin())
                            <div class="text-xs text-blue-600 font-semibold">Administrator</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Profile -->
                <x-responsive-nav-link :href="route('profile.edit')">
                    <i class="fas fa-user mr-2"></i>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Quick Actions -->
                <x-responsive-nav-link :href="route('bank-statements.create')">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>
                    {{ __('Upload Statement') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('chat-sessions.create')">
                    <i class="fas fa-comment-medical mr-2"></i>
                    {{ __('New Chat') }}
                </x-responsive-nav-link>

                @if(auth()->user()->isAdmin())
                    <!-- Clear Cache -->
                    <form method="POST" action="{{ route('dashboard.clear-cache') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('dashboard.clear-cache')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            <i class="fas fa-trash mr-2"></i>
                            {{ __('Clear Cache') }}
                        </x-responsive-nav-link>
                    </form>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Add FontAwesome if not already included -->
@once
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @endpush
@endonce