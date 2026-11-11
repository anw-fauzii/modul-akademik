<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Login') }} - Portal Aplikasi</title>

    <!-- Fonts & Assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 antialiased font-sans selection:bg-orange-500 selection:text-white">
    <div class="min-h-screen flex w-full">
        <!-- Sisi Kiri: Banner & Carousel Quotes (Desktop) -->
        <div class="hidden lg:flex lg:w-8/12 bg-gradient-to-br from-orange-600 via-amber-600 to-orange-900 text-white p-12 flex-col justify-between relative overflow-hidden shadow-2xl">
            <!-- Aksen Dekoratif Cahaya & Pola -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1.5px,transparent_1.5px)] [background-size:24px_24px]"></div>
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-black/20 blur-3xl pointer-events-none"></div>
            
            <!-- Header Badge -->
            <div class="relative z-10 flex items-center justify-between">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold tracking-wide border border-white/20 shadow-sm">
                    <svg class="w-4 h-4 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    EKOSISTEM DIGITAL TERINTEGRASI
                </span>
                <span class="text-xs text-orange-200 font-medium tracking-wider uppercase">Yayasan Pendidikan</span>
            </div>

            <!-- Kontainer Carousel Quotes -->
            <div class="relative z-10 max-w-xl my-auto min-h-[180px] flex flex-col justify-center">
                <div id="quote-carousel" class="transition-all duration-700 ease-in-out opacity-100 transform translate-y-0">
                    <blockquote id="quote-text" class="text-2xl xl:text-3xl font-bold leading-snug mb-4 tracking-tight drop-shadow-sm">
                        "Prestasi tidak selalu lahir dari persiapan panjang dan fasilitas yang sempurna. Terkadang, ia tumbuh dari keberanian menerima tantangan..."
                    </blockquote>
                    <p id="quote-author" class="text-orange-100 text-sm font-medium tracking-wide">
                        — Inspirasi Prestasi Siswa Hebat
                    </p>
                </div>
            </div>

            <!-- Footer / Indikator Slider & Keamanan -->
            <div class="relative z-10 flex items-center justify-between text-xs text-orange-200">
                <!-- Titik Indikator Carousel -->
                <div class="flex items-center gap-2" id="carousel-indicators">
                    <button onclick="changeQuote(0)" class="w-8 h-1.5 bg-white rounded-full transition-all duration-300 indicator-btn"></button>
                    <button onclick="changeQuote(1)" class="w-2 h-1.5 bg-white/40 rounded-full transition-all duration-300 indicator-btn"></button>
                    <button onclick="changeQuote(2)" class="w-2 h-1.5 bg-white/40 rounded-full transition-all duration-300 indicator-btn"></button>
                </div>
                
                <div class="flex items-center gap-1.5 bg-black/10 px-3 py-1.5 rounded-lg backdrop-blur-sm">
                    <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Autentikasi Terpusat SSO</span>
                </div>
            </div>
        </div>

        <!-- Sisi Kanan: Area Login SSO -->
        <div class="w-full lg:w-4/12 flex flex-col justify-between bg-white px-8 sm:px-12 lg:px-16 py-10 shadow-xl z-20">
            <div class="w-full max-w-sm mx-auto my-auto space-y-7">
                
                <!-- Kotak Logo Yayasan -->
                <div class="flex items-center space-x-4 p-3.5 bg-orange-50/60 border border-orange-100 rounded-2xl">
                    <div class="flex items-center">
                        <img src="{{ asset('storage/logo/samping.png') }}" alt="Logo Yayasan" class="h-16 w-auto object-contain">
                    </div>
                </div>

                <!-- Judul & Subjudul -->
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">{{ __('Selamat Datang') }}</h1>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        Aplikasi ini terhubung dengan sistem <strong>Single Sign-On (SSO)</strong>. Silakan masuk menggunakan satu akun terpadu Anda.
                    </p>
                </div>

                <!-- Kartu Informasi Keamanan SSO -->
                <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-orange-100 text-orange-600 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-gray-800">Satu Akun untuk Semua Akses</h3>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-normal">
                                Anda akan diarahkan ke halaman verifikasi utama sebelum masuk ke dasbor aplikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Login with SSO -->
                <div class="pt-1">
                    <a href="{{ route('sso.login') }}" class="w-full py-3.5 px-5 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 active:scale-[0.99] text-white font-bold rounded-xl shadow-lg shadow-orange-600/30 transition-all duration-200 flex items-center justify-center gap-3 text-sm group">
                        <svg class="w-5 h-5 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>{{ __('Login with SSO') }}</span>
                        <svg class="w-4 h-4 ml-auto group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

                <!-- Bantuan / Kendala -->
                <div class="text-center pt-2">
                    <p class="text-xs text-gray-400">
                        Mengalami kendala akses? <a href="#" class="text-orange-600 font-bold hover:underline">Hubungi Admin IT</a>
                    </p>
                </div>
            </div>

            <!-- Footer Hak Cipta -->
            <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-400 gap-2">
                <p>&copy; 2026 Yayasan Pendidikan.</p>
                <div class="flex items-center gap-3">
                    <a href="#" class="hover:text-gray-600 transition">Kebijakan Privasi</a>
                    <span>&bull;</span>
                    <a href="#" class="hover:text-gray-600 transition">Ketentuan</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript untuk Carousel Quotes Otomatis & Interaktif -->
    <script>
        const quotes = [
            {
                text: `"Prestasi tidak selalu lahir dari persiapan panjang dan fasilitas yang sempurna. Terkadang, ia tumbuh dari keberanian menerima tantangan..."`,
                author: `— Inspirasi Prestasi Siswa Hebat`
            },
            {
                text: `"Pendidikan bukanlah persiapan untuk hidup; pendidikan adalah hidup itu sendiri yang terus berkembang setiap hari."`,
                author: `— John Dewey — Filosofi Pembelajaran`
            },
            {
                text: `"Inovasi membedakan antara seorang pemimpin dan pengikut. Mari bersama membangun ekosistem digital lembaga yang unggul."`,
                author: `— Portal Layanan Terpadu SSO`
            }
        ];

        let currentIndex = 0;
        const quoteTextElement = document.getElementById('quote-text');
        const quoteAuthorElement = document.getElementById('quote-author');
        const carouselContainer = document.getElementById('quote-carousel');
        const indicators = document.querySelectorAll('.indicator-btn');

        function updateQuote(index) {
            carouselContainer.style.opacity = '0';
            carouselContainer.style.transform = 'translateY(10px)';

            setTimeout(() => {
                quoteTextElement.innerText = quotes[index].text;
                quoteAuthorElement.innerText = quotes[index].author;
                
                carouselContainer.style.opacity = '1';
                carouselContainer.style.transform = 'translateY(0)';
            }, 300);

            indicators.forEach((btn, i) => {
                if (i === index) {
                    btn.classList.remove('w-2', 'bg-white/40');
                    btn.classList.add('w-8', 'bg-white');
                } else {
                    btn.classList.remove('w-8', 'bg-white');
                    btn.classList.add('w-2', 'bg-white/40');
                }
            });
        }

        function changeQuote(index) {
            currentIndex = index;
            updateQuote(currentIndex);
        }

        setInterval(() => {
            currentIndex = (currentIndex + 1) % quotes.length;
            updateQuote(currentIndex);
        }, 6000);
    </script>
</body>
</html>
