<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - PION Kepegawaian Prima Insani</title>
    <link rel="icon" href="{{ asset('storage/logo/samping.png') }}" type="image/png">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- App CSS & JS via Vite -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        @keyframes pulse-subtle {
            0%, 100% { opacity: 0.15; transform: scale(1); }
            50% { opacity: 0.28; transform: scale(1.04); }
        }

        .animate-subtle-glow {
            animation: pulse-subtle 6s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased font-sans selection:bg-orange-500 selection:text-white min-h-screen">
    <div class="min-h-screen flex w-full">

        <!-- ========================================================
             SISI KIRI: Banner Inspiratif & Gradient Oranye (Desktop)
             ======================================================== -->
        <div class="hidden lg:flex lg:w-7/12 xl:w-8/12 bg-gradient-to-br from-orange-600 via-amber-600 to-orange-900 text-white p-12 xl:p-14 flex-col justify-between relative overflow-hidden shadow-2xl">
            <!-- Aksen Dekoratif Cahaya & Pola Titik -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1.5px,transparent_1.5px)] [background-size:24px_24px]"></div>
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none animate-subtle-glow"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-black/25 blur-3xl pointer-events-none animate-subtle-glow" style="animation-delay: 2s;"></div>
            
            <!-- Watermark Angka Error Besar di Latar Belakang -->
            <div class="absolute right-6 top-1/2 -translate-y-1/2 text-[14rem] xl:text-[18rem] font-black text-white/5 select-none pointer-events-none tracking-tighter leading-none">
                @yield('code')
            </div>

            <!-- Header Badge Atas -->
            <div class="relative z-10 flex items-center justify-between">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold tracking-wide border border-white/20 shadow-sm">
                    <svg class="w-4 h-4 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>EKOSISTEM DIGITAL TERINTEGRASI</span>
                </span>
                <span class="text-xs text-orange-200 font-medium tracking-wider uppercase">Yayasan Pendidikan Prima Insani</span>
            </div>

            <!-- Kontainer Quotes & Pesan Tengah -->
            <div class="relative z-10 max-w-xl my-auto min-h-[180px] flex flex-col justify-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-xs font-bold text-orange-100 w-fit mb-4 border border-white/15">
                    <span class="w-2 h-2 rounded-full bg-amber-300 animate-ping"></span>
                    <span>Status Sistem &bull; Respon HTTP @yield('code')</span>
                </div>
                <blockquote class="text-2xl xl:text-3xl font-bold leading-snug mb-4 tracking-tight drop-shadow-sm">
                    "@yield('quote', 'Prestasi tidak selalu lahir dari persiapan panjang dan fasilitas yang sempurna. Terkadang, ia tumbuh dari keberanian menerima tantangan dan menemukan jalan kembali...')"
                </blockquote>
                <p class="text-orange-100 text-sm font-medium tracking-wide">
                    — @yield('quote_author', 'Sistem Informasi Prima Insani Terpadu (SIPIT)')
                </p>
            </div>

            <!-- Footer Kiri: Status & Indikator -->
            <div class="relative z-10 flex items-center justify-between text-xs text-orange-200">
                <!-- Titik Indikator Visual -->
                <div class="flex items-center gap-2">
                    <span class="w-8 h-1.5 bg-white rounded-full transition-all duration-300"></span>
                    <span class="w-2 h-1.5 bg-white/40 rounded-full"></span>
                    <span class="w-2 h-1.5 bg-white/40 rounded-full"></span>
                </div>
                
                <div class="flex items-center gap-1.5 bg-black/15 px-3 py-1.5 rounded-lg backdrop-blur-sm border border-white/10">
                    <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Layanan Presensi & Kepegawaian Aktif</span>
                </div>
            </div>
        </div>

        <!-- ========================================================
             SISI KANAN: Panel Pesan Error, Penjelasan & Aksi
             ======================================================== -->
        <div class="w-full lg:w-5/12 xl:w-4/12 flex flex-col justify-between bg-white px-7 sm:px-12 lg:px-14 py-8 sm:py-10 shadow-xl z-20">
            <div class="w-full max-w-sm mx-auto my-auto space-y-6">
                
                <!-- Kotak Logo Yayasan (Identik dengan Desain Login) -->
                <div class="flex items-center space-x-4 p-3.5 bg-orange-50/60 border border-orange-100 rounded-2xl shadow-2xs">
                    <div class="flex items-center">
                        <img src="{{ asset('storage/logo/samping.png') }}" 
                             alt="Logo Yayasan Prima Insani" 
                             class="h-14 sm:h-16 w-auto object-contain"
                             onerror="this.style.display='none'">
                    </div>
                </div>

                <!-- Judul & Badge Status Error -->
                <div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-50 border border-orange-200/80 text-orange-700 font-extrabold text-[11px] uppercase tracking-wide mb-2 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                        <span>@yield('badge')</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight leading-snug">
                        @yield('heading')
                    </h1>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        @yield('description')
                    </p>
                </div>

                <!-- Kartu Informasi / Petunjuk Solusi (Identik dengan Style Login) -->
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-3 shadow-2xs">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-orange-100 text-orange-600 mt-0.5 shrink-0 shadow-2xs">
                            @yield('icon')
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-800">@yield('hint_title', 'Petunjuk Navigasi')</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-normal">
                                @yield('hint')
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Navigasi Aksi (Identik dengan Style Login Button) -->
                <div class="pt-1 space-y-2.5">
                    <a href="{{ url('/home') }}" class="w-full py-3.5 px-5 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 active:scale-[0.99] text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all duration-200 flex items-center justify-center gap-3 text-sm group cursor-pointer">
                        <svg class="w-4 h-4 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Ke Halaman Beranda</span>
                        <svg class="w-4 h-4 ml-auto group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>

                    <button type="button" onclick="handleGoBack()" class="w-full py-3 px-5 bg-white hover:bg-orange-50/70 text-gray-700 hover:text-orange-900 font-bold rounded-xl border border-gray-200 hover:border-orange-200 transition-all duration-200 flex items-center justify-center gap-2 text-xs cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Halaman Sebelumnya</span>
                    </button>
                </div>

                <!-- Bantuan / Kendala Akses -->
                <div class="text-center pt-1">
                    <p class="text-xs text-gray-400">
                        Mengalami kendala sistem? <a href="mailto:it@primainsani.sch.id" class="text-orange-600 font-bold hover:underline">Hubungi Admin IT</a>
                    </p>
                </div>

                <!-- Pintasan Keyboard di Desktop -->
                <div class="hidden sm:flex items-center justify-center gap-3 text-[10px] text-gray-400 font-medium pt-1">
                    <span>Tekan <kbd class="px-1.5 py-0.5 text-[9px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-600 shadow-2xs">Esc</kbd> untuk kembali</span>
                    <span>&bull;</span>
                    <span>Tekan <kbd class="px-1.5 py-0.5 text-[9px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-600 shadow-2xs">↵ Enter</kbd> ke beranda</span>
                </div>

            </div>

            <!-- Footer Hak Cipta (Identik dengan Login) -->
            <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-400 gap-2">
                <span>&copy; {{ date('Y') }} Yayasan Pendidikan Prima Insani</span>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="window.location.reload()" class="hover:text-orange-600 transition-colors">Muat Ulang</button>
                    <span>&bull;</span>
                    <a href="{{ url('/') }}" class="hover:text-orange-600 transition-colors">Portal SSO</a>
                </div>
            </div>
        </div>

    </div>

    <!-- Interactive Navigation Scripts -->
    <script>
        function handleGoBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = "{{ url('/') }}";
            }
        }

        // Keyboard Shortcuts (Esc = Back, Enter = Home)
        document.addEventListener('keydown', function(event) {
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') return;

            if (event.key === 'Escape') {
                event.preventDefault();
                handleGoBack();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                window.location.href = "{{ url('/home') }}";
            }
        });
    </script>
</body>
</html>
