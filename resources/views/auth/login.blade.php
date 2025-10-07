<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk • MatchFinance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        body {
            background-color: #0f172a;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.1) 1px, transparent 1px),
                linear-gradient(to right, rgba(148, 163, 184, 0.1) 1px, transparent 1px);
            background-size: 3rem 3rem;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Gradient Animation */
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899, #3b82f6);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 4s ease infinite;
        }

        /* Pulse Glow */
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
            }
            50% { 
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.6);
            }
        }

        .chart-float {
            animation: float 6s ease-in-out infinite;
        }

        /* Fade In Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        /* Input Focus Effect */
        .input-group input:focus {
            transform: translateY(-2px);
        }

        /* Button Ripple */
        .btn-primary {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
        }

        /* Floating Particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.8), transparent);
            border-radius: 50%;
            animation: float-particle 20s infinite ease-in-out;
            pointer-events: none;
        }

        @keyframes float-particle {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(80px, -80px); }
            50% { transform: translate(-40px, -160px); }
            75% { transform: translate(120px, -120px); }
        }

        /* Chart Animation */
        .bar-chart div {
            animation: grow-bar 1.5s ease-out forwards;
        }

        @keyframes grow-bar {
            from { height: 0; }
        }

        /* Stats Card Animation */
        @keyframes stat-pop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        .stat-card {
            animation: stat-pop 0.6s ease-out forwards;
        }
    </style>
</head>
<body class="antialiased">
    
    <!-- Floating Particles -->
    <div class="particle" style="width: 100px; height: 100px; top: 15%; left: 10%; opacity: 0.2; animation-delay: 0s;"></div>
    <div class="particle" style="width: 60px; height: 60px; top: 70%; right: 15%; opacity: 0.15; animation-delay: 7s;"></div>
    <div class="particle" style="width: 80px; height: 80px; bottom: 10%; left: 20%; opacity: 0.2; animation-delay: 14s;"></div>

    <div class="min-h-screen flex items-center justify-center p-4 lg:p-8">
        <div class="grid w-full max-w-6xl grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
            
            <!-- Left Side - Chart Visualization -->
            <div class="flex flex-col items-center justify-center space-y-8 animate-fade-in-up">
                
                <!-- Logo & Title -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-6" style="animation: pulse-glow 3s ease-in-out infinite;">
                        <i class="fas fa-chart-line text-white text-3xl"></i>
                    </div>
                    <h2 class="text-4xl font-extrabold text-white mb-3">
                        Ekosistem <span class="gradient-text">Finansial</span>
                    </h2>
                    <p class="text-gray-400 text-lg max-w-md">
                        Platform terintegrasi untuk mengelola keuangan pribadi dan bisnis Anda dengan cerdas
                    </p>
                </div>

                <!-- Animated Chart -->
                <div class="chart-float w-full max-w-md bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-8 border border-slate-700 shadow-2xl">
                    
                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="stat-card bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700" style="animation-delay: 0.2s;">
                            <i class="fas fa-arrow-up text-green-500 text-xl mb-2"></i>
                            <div class="text-2xl font-bold text-white">+24%</div>
                            <div class="text-xs text-gray-400 mt-1">Pendapatan</div>
                        </div>
                        <div class="stat-card bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700" style="animation-delay: 0.4s;">
                            <i class="fas fa-wallet text-blue-500 text-xl mb-2"></i>
                            <div class="text-2xl font-bold text-white">Rp 45M</div>
                            <div class="text-xs text-gray-400 mt-1">Saldo</div>
                        </div>
                        <div class="stat-card bg-slate-800/50 rounded-xl p-4 text-center border border-slate-700" style="animation-delay: 0.6s;">
                            <i class="fas fa-chart-pie text-purple-500 text-xl mb-2"></i>
                            <div class="text-2xl font-bold text-white">156</div>
                            <div class="text-xs text-gray-400 mt-1">Transaksi</div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="bar-chart flex items-end justify-between h-40 space-x-3">
                        <div class="flex-1 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg" style="height: 60%;"></div>
                        <div class="flex-1 bg-gradient-to-t from-purple-600 to-purple-400 rounded-t-lg" style="height: 85%;"></div>
                        <div class="flex-1 bg-gradient-to-t from-pink-600 to-pink-400 rounded-t-lg" style="height: 45%;"></div>
                        <div class="flex-1 bg-gradient-to-t from-teal-600 to-teal-400 rounded-t-lg" style="height: 75%;"></div>
                        <div class="flex-1 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg" style="height: 95%;"></div>
                        <div class="flex-1 bg-gradient-to-t from-purple-600 to-purple-400 rounded-t-lg" style="height: 70%;"></div>
                    </div>
                    
                    <div class="flex justify-between text-xs text-gray-500 mt-3 px-1">
                        <span>Jan</span>
                        <span>Feb</span>
                        <span>Mar</span>
                        <span>Apr</span>
                        <span>Mei</span>
                        <span>Jun</span>
                    </div>
                </div>

                <!-- Feature Badges -->
                <div class="flex flex-wrap gap-3 justify-center max-w-md">
                    <div class="bg-slate-800/50 border border-slate-700 rounded-full px-4 py-2 flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-green-500"></i>
                        <span class="text-sm text-gray-300">Bank-Level Security</span>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-full px-4 py-2 flex items-center space-x-2">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        <span class="text-sm text-gray-300">Real-time Sync</span>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700 rounded-full px-4 py-2 flex items-center space-x-2">
                        <i class="fas fa-mobile-alt text-blue-500"></i>
                        <span class="text-sm text-gray-300">Multi-Platform</span>
                    </div>
                </div>

            </div>

            <!-- Right Side - Login Form -->
            <div class="w-full animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 lg:p-10 rounded-3xl shadow-2xl border border-slate-700">
                    
                    <div class="mb-8">
                        <h1 class="text-4xl font-extrabold text-white mb-2">Selamat Datang! 👋</h1>
                        <p class="text-gray-400 text-base">Masuk untuk melanjutkan ke dashboard Anda</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        
                        <!-- Email Input -->
                        <div class="input-group">
                            <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">
                                Email
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                <input 
                                    id="email" 
                                    name="email" 
                                    type="email" 
                                    required 
                                    autocomplete="email" 
                                    autofocus
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-4 py-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-lg"
                                    placeholder="nama@email.com"
                                    value="{{ old('email') }}">
                            </div>
                            @error('email')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Password Input -->
                        <div class="input-group">
                            <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                <input 
                                    id="password" 
                                    name="password" 
                                    type="password" 
                                    required 
                                    autocomplete="current-password"
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-12 py-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-lg"
                                    placeholder="••••••••">
                                <button 
                                    type="button" 
                                    id="togglePassword" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                    <i class="fa-solid fa-eye" id="eye-icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex justify-between items-center text-sm">
                            <label class="flex items-center text-gray-400 cursor-pointer select-none group">
                                <input 
                                    type="checkbox" 
                                    name="remember" 
                                    class="form-checkbox h-5 w-5 text-blue-600 rounded border-slate-700 bg-slate-900/50 focus:ring-blue-500 focus:ring-offset-0 transition">
                                <span class="ml-2 group-hover:text-gray-300 transition">Ingat saya</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-blue-500 hover:text-blue-400 font-semibold hover:underline transition">
                                    Lupa Password?
                                </a>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="btn-primary w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 rounded-xl text-base font-bold shadow-2xl relative z-10">
                            <span class="relative z-10 flex items-center justify-center">
                                Masuk
                                <i class="fas fa-arrow-right ml-2"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-gradient-to-br from-slate-800 to-slate-900 text-gray-400">
                                Atau masuk dengan
                            </span>
                        </div>
                    </div>

                    <!-- Social Login -->
                    <div class="grid grid-cols-2 gap-4">
                        <button class="flex items-center justify-center space-x-2 bg-slate-900/50 border border-slate-700 rounded-xl py-3 hover:bg-slate-800 transition-all hover:border-blue-500">
                            <i class="fab fa-google text-red-500 text-lg"></i>
                            <span class="text-gray-300 font-medium">Google</span>
                        </button>
                        <button class="flex items-center justify-center space-x-2 bg-slate-900/50 border border-slate-700 rounded-xl py-3 hover:bg-slate-800 transition-all hover:border-blue-500">
                            <i class="fab fa-github text-white text-lg"></i>
                            <span class="text-gray-300 font-medium">GitHub</span>
                        </button>
                    </div>

                    <!-- Register Link -->
                    <p class="text-center text-gray-400 mt-8">
                        Belum punya akun? 
                        <a href="{{ route('register') }}" class="text-blue-500 hover:text-blue-400 font-semibold hover:underline transition">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toggle Password Visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    eyeIcon.classList.toggle('fa-eye');
                    eyeIcon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>