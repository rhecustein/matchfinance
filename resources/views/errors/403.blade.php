<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied • {{ config('app.name', 'MatchFinance') }}</title>
    @vite(['resources/css/app.css'])
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

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.3); }
            50% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.5); }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-2xl w-full">
        <!-- Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-32 h-32 bg-red-500/10 border-4 border-red-500/30 rounded-full pulse-glow float-animation">
                <i class="fas fa-ban text-6xl text-red-500"></i>
            </div>
        </div>

        <!-- Main Error Card -->
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700 rounded-2xl p-8 shadow-2xl">
            
            <!-- Error Code -->
            <div class="text-center mb-6">
                <h1 class="text-8xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-pink-500 mb-4">
                    403
                </h1>
                <h2 class="text-2xl font-bold text-white mb-2">Access Denied</h2>
                <p class="text-gray-400">{{ $exception->getMessage() ?: 'You do not have permission to access this resource.' }}</p>
            </div>

            <!-- Divider -->
            <div class="border-t border-slate-700 my-6"></div>

            <!-- Detailed Information -->
            @auth
                @php
                    $user = auth()->user();
                    $company = $user->company;
                @endphp

                <div class="space-y-4 mb-6">
                    <!-- User Info -->
                    <div class="flex items-start space-x-3 p-4 bg-slate-900/50 rounded-lg border border-slate-700">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white text-sm font-bold">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-white">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                            <p class="text-xs text-purple-400 mt-1">
                                Role: 
                                @if($user->isSuperAdmin())
                                    <span class="text-red-400 font-semibold">Super Admin</span>
                                @elseif($user->isOwner())
                                    <span class="text-blue-400 font-semibold">Owner</span>
                                @elseif($user->isAdmin())
                                    <span class="text-green-400 font-semibold">Admin</span>
                                @elseif($user->isManager())
                                    <span class="text-yellow-400 font-semibold">Manager</span>
                                @elseif($user->isStaff())
                                    <span class="text-teal-400 font-semibold">Staff</span>
                                @else
                                    <span class="text-gray-400 font-semibold">User</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Company Status (if applicable) -->
                    @if($company)
                        <div class="p-4 rounded-lg border {{ $company->status === 'active' ? 'bg-green-500/10 border-green-500/30' : 'bg-red-500/10 border-red-500/30' }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-building text-{{ $company->status === 'active' ? 'green' : 'red' }}-500"></i>
                                    <span class="text-sm font-semibold text-white">{{ $company->name }}</span>
                                </div>
                                <span class="px-3 py-1 text-xs font-bold rounded-full {{ $company->status === 'active' ? 'bg-green-500/20 text-green-400' : $company->status === 'inactive' ? 'bg-red-500/20 text-red-400' : ($company->status === 'suspended' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-gray-500/20 text-gray-400') }}">
                                    {{ strtoupper($company->status) }}
                                </span>
                            </div>

                            @if($company->status !== 'active')
                                <div class="mt-3 p-3 bg-slate-900/50 rounded-lg border border-slate-700">
                                    <p class="text-sm text-gray-300 mb-2">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                        @if($company->status === 'inactive')
                                            <strong>Company Account Inactive:</strong> Your company account is currently inactive. 
                                        @elseif($company->status === 'suspended')
                                            <strong>Company Account Suspended:</strong> Your company account has been suspended.
                                        @elseif($company->status === 'cancelled')
                                            <strong>Company Account Cancelled:</strong> Your company account has been cancelled.
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Please contact your administrator or support team for assistance.
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- User Account Status -->
                    @if(!$user->isActive())
                        <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                            <div class="flex items-start space-x-2">
                                <i class="fas fa-user-slash text-yellow-500 mt-1"></i>
                                <div>
                                    <p class="text-sm font-semibold text-yellow-400 mb-1">Your Account Status</p>
                                    <ul class="text-xs text-gray-300 space-y-1">
                                        @if(!$user->is_active)
                                            <li>• Account is not active</li>
                                        @endif
                                        @if($user->is_suspended)
                                            <li>• Account is suspended
                                                @if($user->suspension_reason)
                                                    <span class="text-gray-400">({{ $user->suspension_reason }})</span>
                                                @endif
                                            </li>
                                        @endif
                                        @if($user->isLocked())
                                            <li>• Account is temporarily locked until {{ $user->locked_until->format('d M Y H:i') }}</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endauth

            <!-- Possible Solutions -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-white mb-3 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    What can you do?
                </h3>
                <ul class="space-y-2 text-sm text-gray-300">
                    @auth
                        @if($user->company && $user->company->status !== 'active')
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-chevron-right text-blue-500 mt-1 text-xs"></i>
                                <span>Contact your company administrator to activate the account</span>
                            </li>
                        @endif
                        @if(!$user->isActive())
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-chevron-right text-blue-500 mt-1 text-xs"></i>
                                <span>Contact your account administrator to activate your user account</span>
                            </li>
                        @endif
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-chevron-right text-blue-500 mt-1 text-xs"></i>
                            <span>Return to dashboard and try accessing a different resource</span>
                        </li>
                    @else
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-chevron-right text-blue-500 mt-1 text-xs"></i>
                            <span>Login with an authorized account</span>
                        </li>
                    @endauth
                    <li class="flex items-start space-x-2">
                        <i class="fas fa-chevron-right text-blue-500 mt-1 text-xs"></i>
                        <span>Contact support if you believe this is an error</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                @auth
                    @if($user->isSuperAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:shadow-xl transition text-center">
                            <i class="fas fa-tachometer-alt mr-2"></i>Go to Admin Dashboard
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:shadow-xl transition text-center">
                            <i class="fas fa-home mr-2"></i>Go to Dashboard
                        </a>
                    @endif
                    
                    <a href="mailto:support@matchfinance.com" class="flex-1 px-6 py-3 bg-slate-700 text-white rounded-lg font-semibold hover:bg-slate-600 transition text-center border border-slate-600">
                        <i class="fas fa-envelope mr-2"></i>Contact Support
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:shadow-xl transition text-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                @endauth
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                Need help? Visit our <a href="#" class="text-blue-400 hover:text-blue-300 transition underline">Help Center</a>
            </p>
        </div>
    </div>

</body>
</html>