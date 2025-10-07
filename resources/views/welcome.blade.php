<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome • MatchFinance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
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

        /* Floating Animation untuk Cards */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        @keyframes float-reverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(-2deg); }
        }

        /* Gradient Text Animation */
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

        /* Pulse Animation untuk Icons */
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.8);
                transform: scale(1.05);
            }
        }

        .feature-card {
            animation: float 6s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .feature-card:nth-child(even) {
            animation: float-reverse 6s ease-in-out infinite;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.05) !important;
        }

        .icon-box {
            animation: pulse-glow 3s ease-in-out infinite;
        }

        /* Stats Counter Animation */
        @keyframes count-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-item {
            animation: count-up 0.8s ease-out forwards;
        }

        /* Button Hover Effects */
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
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
        }

        /* Fade In Animations */
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
            animation: fadeInUp 1s ease-out forwards;
            opacity: 0;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .delay-600 { animation-delay: 0.6s; }

        /* Floating Particles Background */
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.8), transparent);
            border-radius: 50%;
            animation: float-particle 20s infinite ease-in-out;
            pointer-events: none;
        }

        @keyframes float-particle {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(100px, -100px); }
            50% { transform: translate(-50px, -200px); }
            75% { transform: translate(150px, -150px); }
        }
    </style>
</head>
<body class="antialiased">
    
    <!-- Floating Particles -->
    <div class="particle" style="width: 80px; height: 80px; top: 10%; left: 10%; opacity: 0.3; animation-delay: 0s;"></div>
    <div class="particle" style="width: 60px; height: 60px; top: 60%; right: 15%; opacity: 0.2; animation-delay: 5s;"></div>
    <div class="particle" style="width: 100px; height: 100px; bottom: 20%; left: 20%; opacity: 0.25; animation-delay: 10s;"></div>

    <div class="min-h-screen flex flex-col">
        
        <!-- Navigation -->
        <nav class="relative z-10 p-6 lg:p-8">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-3 animate-fade-in-up">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-white">MatchFinance</span>
                </div>
                
                <div class="flex items-center space-x-4 animate-fade-in-up delay-200">
                    <a href="{{ route('login') }}" class="text-gray-300 hover:text-white transition px-4 py-2 rounded-lg hover:bg-slate-800">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                        Daftar
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
                
                <!-- Left Content -->
                <div class="text-center lg:text-left space-y-6">
                    <h1 class="text-5xl lg:text-7xl font-extrabold animate-fade-in-up">
                        <span class="text-white">Kelola Keuangan</span><br>
                        <span class="gradient-text">Lebih Cerdas</span>
                    </h1>
                    
                    <p class="text-xl text-gray-400 max-w-2xl animate-fade-in-up delay-200">
                        Platform manajemen keuangan terpadu untuk membantu Anda mencapai tujuan finansial dengan mudah dan efisien.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up delay-300">
                        <a href="{{ route('register') }}" class="btn-primary bg-blue-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-2xl relative z-10">
                            Mulai Sekarang
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="#features" class="border-2 border-slate-700 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-slate-800 transition">
                            Pelajari Lebih Lanjut
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 pt-8 animate-fade-in-up delay-400">
                        <div class="stat-item text-center">
                            <div class="text-3xl font-bold text-blue-500">10K+</div>
                            <div class="text-sm text-gray-400 mt-1">Pengguna Aktif</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="text-3xl font-bold text-purple-500">50M+</div>
                            <div class="text-sm text-gray-400 mt-1">Transaksi</div>
                        </div>
                        <div class="stat-item text-center">
                            <div class="text-3xl font-bold text-pink-500">99.9%</div>
                            <div class="text-sm text-gray-400 mt-1">Uptime</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Feature Cards -->
                <div class="grid grid-cols-2 gap-6 animate-fade-in-up delay-500">
                    
                    <!-- Card 1 -->
                    <div class="feature-card bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-blue-500 transition-all shadow-xl">
                        <div class="icon-box w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-wallet text-white text-2xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-lg mb-2">Smart Budgeting</h3>
                        <p class="text-gray-400 text-sm">Kelola anggaran dengan AI assistant</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="feature-card bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-purple-500 transition-all shadow-xl">
                        <div class="icon-box w-16 h-16 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-chart-pie text-white text-2xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-lg mb-2">Analytics</h3>
                        <p class="text-gray-400 text-sm">Visualisasi pengeluaran real-time</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="feature-card bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-pink-500 transition-all shadow-xl">
                        <div class="icon-box w-16 h-16 bg-pink-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-shield-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-lg mb-2">Secure</h3>
                        <p class="text-gray-400 text-sm">Enkripsi bank-level security</p>
                    </div>

                    <!-- Card 4 -->
                    <div class="feature-card bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-2xl border border-slate-700 hover:border-teal-500 transition-all shadow-xl">
                        <div class="icon-box w-16 h-16 bg-teal-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-mobile-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-lg mb-2">Mobile First</h3>
                        <p class="text-gray-400 text-sm">Akses dimana saja, kapan saja</p>
                    </div>

                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 text-center py-8 text-gray-500 text-sm">
            <p>&copy; 2025 MatchFinance. Semua hak dilindungi.</p>
        </footer>

    </div>

</body>
</html>