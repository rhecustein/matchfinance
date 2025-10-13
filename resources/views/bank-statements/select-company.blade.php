<x-app-layout>
    <x-slot name="header">Select Company - Upload Bank Statement</x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- INFO MESSAGE --}}
            @if(session('info'))
                <div class="bg-blue-600/20 border border-blue-600 text-blue-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-info-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('info') }}</p>
                </div>
            @endif

            {{-- ERROR MESSAGE --}}
            @if(session('error'))
                <div class="bg-red-600/20 border border-red-600 text-red-400 px-6 py-4 rounded-lg flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-semibold">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Header --}}
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2">Select Company</h2>
                    <p class="text-gray-400">Choose a company to upload bank statements</p>
                </div>
                <a href="{{ route('bank-statements.index') }}" 
                   class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>

            {{-- Super Admin Badge --}}
            <div class="bg-gradient-to-r from-purple-600/20 to-pink-600/20 border border-purple-500 rounded-xl p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div>
                        <p class="text-white font-semibold">Super Admin Mode</p>
                        <p class="text-sm text-purple-300">You can upload statements for any company</p>
                    </div>
                </div>
            </div>

            {{-- Companies Grid --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="fas fa-building mr-2"></i>Available Companies
                </h3>

                @if($companies->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-building text-gray-600 text-5xl mb-4"></i>
                        <p class="text-gray-400 text-lg mb-2">No active companies found</p>
                        <p class="text-gray-500 text-sm">Please add a company first</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($companies as $company)
                            <a href="{{ route('bank-statements.create', ['company_id' => $company->id]) }}" 
                               class="group bg-slate-900/50 rounded-xl p-6 border border-slate-700 hover:border-blue-500 hover:bg-slate-900/80 transition-all cursor-pointer">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h4 class="text-white font-semibold text-lg mb-1 group-hover:text-blue-400 transition">
                                            {{ $company->name }}
                                        </h4>
                                        @if($company->subdomain)
                                            <p class="text-sm text-gray-400">
                                                <i class="fas fa-link mr-1"></i>{{ $company->subdomain }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 transition">
                                        <i class="fas fa-arrow-right text-blue-400 group-hover:text-white transition"></i>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    {{-- Bank Count --}}
                                    <div class="flex items-center space-x-2 text-sm">
                                        <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-university text-gray-400"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-400">Active Banks</p>
                                            <p class="text-white font-semibold">
                                                {{ $company->banks_count }} 
                                                @if($company->banks_count === 0)
                                                    <span class="text-xs text-red-400">(Add banks first)</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Status --}}
                                    <div class="flex items-center space-x-2 text-sm">
                                        <div class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-check-circle text-green-400"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-gray-400">Status</p>
                                            <p class="text-green-400 font-semibold capitalize">{{ $company->status }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if($company->banks_count === 0)
                                    <div class="mt-4 bg-yellow-600/10 border border-yellow-500/30 rounded-lg p-3">
                                        <p class="text-xs text-yellow-400">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            This company has no banks. Upload will be disabled.
                                        </p>
                                    </div>
                                @else
                                    <div class="mt-4 flex items-center justify-between text-sm text-blue-400 group-hover:text-blue-300 transition">
                                        <span>Click to upload statements</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quick Actions Card --}}
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700 shadow-xl">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-bolt mr-2 text-yellow-400"></i>Quick Actions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('admin.companies.index') }}" 
                       class="flex items-center space-x-3 p-4 bg-slate-900/50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                        <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Manage Companies</p>
                            <p class="text-sm text-gray-400">Add or edit companies</p>
                        </div>
                    </a>
                    <a href="{{ route('banks.index') }}" 
                       class="flex items-center space-x-3 p-4 bg-slate-900/50 rounded-lg border border-slate-700 hover:border-purple-500 transition">
                        <div class="w-10 h-10 bg-purple-600/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-university text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold">Manage Banks</p>
                            <p class="text-sm text-gray-400">Add banks for companies</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>