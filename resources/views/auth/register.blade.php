<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar • MatchFinance</title>
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

        @keyframes float-reverse {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
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
                box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            }
            50% { 
                box-shadow: 0 0 40px rgba(139, 92, 246, 0.6);
            }
        }

        .feature-float {
            animation: float 6s ease-in-out infinite;
        }

        .feature-float:nth-child(even) {
            animation: float-reverse 6s ease-in-out infinite;
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
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.4);
        }

        /* Floating Particles */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.8), transparent);
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

        /* Feature Card Hover */
        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px) scale(1.02);
        }

        /* Password Strength Indicator */
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* Check Animation */
        @keyframes check-pop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .check-icon {
            animation: check-pop 0.3s ease-out;
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
            
            <!-- Left Side - Features -->
            <div class="flex flex-col items-center justify-center space-y-8 animate-fade-in-up">
                
                <!-- Logo & Title -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-6" style="animation: pulse-glow 3s ease-in-out infinite;">
                        <i class="fas fa-rocket text-white text-3xl"></i>
                    </div>
                    <h2 class="text-4xl font-extrabold text-white mb-3">
                        Mulai Perjalanan <span class="gradient-text">Finansial</span>
                    </h2>
                    <p class="text-gray-400 text-lg max-w-md">
                        Bergabunglah dengan ribuan pengguna yang sudah mengelola keuangan mereka dengan lebih baik
                    </p>
                </div>

                <!-- Feature Cards Grid -->
                <div class="grid grid-cols-2 gap-4 w-full max-w-md">
                    
                    <div class="feature-card feature-float bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-blue-500 shadow-xl">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-3">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-base mb-1">Smart Analytics</h3>
                        <p class="text-gray-400 text-xs">Analisis keuangan otomatis</p>
                    </div>

                    <div class="feature-card feature-float bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-purple-500 shadow-xl" style="animation-delay: 0.2s;">
                        <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-3">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-base mb-1">100% Aman</h3>
                        <p class="text-gray-400 text-xs">Enkripsi tingkat bank</p>
                    </div>

                    <div class="feature-card feature-float bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-pink-500 shadow-xl" style="animation-delay: 0.4s;">
                        <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center mb-3">
                            <i class="fas fa-bell text-white text-xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-base mb-1">Notifikasi</h3>
                        <p class="text-gray-400 text-xs">Real-time alerts</p>
                    </div>

                    <div class="feature-card feature-float bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-teal-500 shadow-xl" style="animation-delay: 0.6s;">
                        <div class="w-12 h-12 bg-teal-600 rounded-xl flex items-center justify-center mb-3">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-base mb-1">Multi-User</h3>
                        <p class="text-gray-400 text-xs">Kolaborasi tim</p>
                    </div>

                </div>

                <!-- Benefits List -->
                <div class="w-full max-w-md space-y-3">
                    <div class="flex items-center space-x-3 text-gray-300">
                        <div class="w-8 h-8 bg-green-600/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-500 text-sm check-icon"></i>
                        </div>
                        <span class="text-sm">Gratis selamanya untuk fitur dasar</span>
                    </div>
                    <div class="flex items-center space-x-3 text-gray-300">
                        <div class="w-8 h-8 bg-green-600/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-500 text-sm check-icon"></i>
                        </div>
                        <span class="text-sm">Tanpa biaya tersembunyi</span>
                    </div>
                    <div class="flex items-center space-x-3 text-gray-300">
                        <div class="w-8 h-8 bg-green-600/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-green-500 text-sm check-icon"></i>
                        </div>
                        <span class="text-sm">Support 24/7 dari tim kami</span>
                    </div>
                </div>

            </div>

            <!-- Right Side - Register Form -->
            <div class="w-full animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 lg:p-10 rounded-3xl shadow-2xl border border-slate-700">
                    
                    <div class="mb-8">
                        <h1 class="text-4xl font-extrabold text-white mb-2">Buat Akun Baru 🚀</h1>
                        <p class="text-gray-400 text-base">Isi data diri Anda untuk memulai</p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf
                        
                        <!-- Name Input -->
                        <div class="input-group">
                            <label for="name" class="block text-sm font-semibold text-gray-300 mb-2">
                                Nama Lengkap
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                <input 
                                    id="name" 
                                    name="name" 
                                    type="text" 
                                    required 
                                    autocomplete="name" 
                                    autofocus
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-4 py-3.5 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-lg"
                                    placeholder="John Doe"
                                    value="{{ old('name') }}">
                            </div>
                            @error('name')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

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
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-4 py-3.5 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-lg"
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
                                    autocomplete="new-password"
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-12 py-3.5 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-lg"
                                    placeholder="Min. 8 karakter">
                                <button 
                                    type="button" 
                                    id="togglePassword" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                    <i class="fa-solid fa-eye" id="eye-icon"></i>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="mt-2 space-y-1">
                                <div class="flex space-x-1">
                                    <div class="strength-bar flex-1 bg-slate-700" id="strength-1"></div>
                                    <div class="strength-bar flex-1 bg-slate-700" id="strength-2"></div>
                                    <div class="strength-bar flex-1 bg-slate-700" id="strength-3"></div>
                                    <div class="strength-bar flex-1 bg-slate-700" id="strength-4"></div>
                                </div>
                                <p class="text-xs text-gray-500" id="strength-text">Kekuatan password</p>
                            </div>
                            @error('password')
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Confirm Password Input -->
                        <div class="input-group">
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-300 mb-2">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-lock-open absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                                <input 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    type="password" 
                                    required 
                                    autocomplete="new-password"
                                    class="block w-full rounded-xl bg-slate-900/50 border-slate-700 pl-12 pr-12 py-3.5 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-lg"
                                    placeholder="Ulangi password">
                                <button 
                                    type="button" 
                                    id="togglePasswordConfirm" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition">
                                    <i class="fa-solid fa-eye" id="eye-icon-confirm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="terms"
                                name="terms"
                                required
                                class="form-checkbox h-5 w-5 text-purple-600 rounded border-slate-700 bg-slate-900/50 focus:ring-purple-500 focus:ring-offset-0 transition mt-0.5">
                            <label for="terms" class="ml-3 text-sm text-gray-400 cursor-pointer select-none">
                                Saya setuju dengan <a href="#" class="text-purple-500 hover:text-purple-400 underline">Syarat & Ketentuan</a> dan <a href="#" class="text-purple-500 hover:text-purple-400 underline">Kebijakan Privasi</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="btn-primary w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 rounded-xl text-base font-bold shadow-2xl relative z-10">
                            <span class="relative z-10 flex items-center justify-center">
                                Daftar Sekarang
                                <i class="fas fa-rocket ml-2"></i>
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-gradient-to-br from-slate-800 to-slate-900 text-gray-400">
                                Atau daftar dengan
                            </span>
                        </div>
                    </div>

                    <!-- Social Register -->
                    <div class="grid grid-cols-2 gap-4">
                        <button class="flex items-center justify-center space-x-2 bg-slate-900/50 border border-slate-700 rounded-xl py-3 hover:bg-slate-800 transition-all hover:border-purple-500">
                            <i class="fab fa-google text-red-500 text-lg"></i>
                            <span class="text-gray-300 font-medium">Google</span>
                        </button>
                        <button class="flex items-center justify-center space-x-2 bg-slate-900/50 border border-slate-700 rounded-xl py-3 hover:bg-slate-800 transition-all hover:border-purple-500">
                            <i class="fab fa-github text-white text-lg"></i>
                            <span class="text-gray-300 font-medium">GitHub</span>
                        </button>
                    </div>

                    <!-- Login Link -->
                    <p class="text-center text-gray-400 mt-6">
                        Sudah punya akun? 
                        <a href="{{ route('login') }}" class="text-purple-500 hover:text-purple-400 font-semibold hover:underline transition">
                            Masuk Sekarang
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

            // Toggle Password Confirmation Visibility
            const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
            const passwordConfirmInput = document.getElementById('password_confirmation');
            const eyeIconConfirm = document.getElementById('eye-icon-confirm');
            
            if (togglePasswordConfirm) {
                togglePasswordConfirm.addEventListener('click', function () {
                    const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordConfirmInput.setAttribute('type', type);
                    eyeIconConfirm.classList.toggle('fa-eye');
                    eyeIconConfirm.classList.toggle('fa-eye-slash');
                });
            }

            // Password Strength Checker
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    // Check password length
                    if (password.length >= 8) strength++;
                    if (password.length >= 12) strength++;
                    
                    // Check for mixed case
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    
                    // Check for numbers and special chars
                    if (/\d/.test(password) && /[^a-zA-Z0-9]/.test(password)) strength++;
                    
                    // Update strength bars
                    const bars = ['strength-1', 'strength-2', 'strength-3', 'strength-4'];
                    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
                    const texts = ['Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
                    const textColors = ['text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500'];
                    
                    bars.forEach((barId, index) => {
                        const bar = document.getElementById(barId);
                        bar.className = 'strength-bar flex-1';
                        if (index < strength) {
                            bar.classList.add(colors[strength - 1]);
                        } else {
                            bar.classList.add('bg-slate-700');
                        }
                    });
                    
                    // Update strength text
                    const strengthText = document.getElementById('strength-text');
                    if (password.length > 0) {
                        strengthText.textContent = texts[strength - 1] || 'Sangat Lemah';
                        strengthText.className = 'text-xs ' + (textColors[strength - 1] || 'text-red-500');
                    } else {
                        strengthText.textContent = 'Kekuatan password';
                        strengthText.className = 'text-xs text-gray-500';
                    }
                });
            }
        });
    </script>
</body>
</html>