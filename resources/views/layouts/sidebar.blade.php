<!-- Mobile Backdrop -->
<div id="mobileSidebarBackdrop"
    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-300 hidden opacity-0 lg:hidden"
    aria-hidden="true"></div>

<!-- Sidebar Container -->
<aside id="appSidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-900 border-r border-slate-800/90 text-slate-300 transition-all duration-300 ease-in-out -translate-x-full lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:w-68 shrink-0 shadow-2xl lg:shadow-none">

    <!-- Sidebar Header / Logo (Pinned Top) -->
    <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-slate-800/80 bg-slate-950/40">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img src="{{ asset('storage/logo/samping.png') }}" class="h-9 w-auto object-contain brightness-110 logo-full"
                alt="Logo SIPIT Yayasan Prima Insani" onerror="this.src='{{ asset('logo.png') }}'; this.onerror=null;">
            <img src="{{ asset('logo.png') }}" class="h-9 w-9 object-contain brightness-110 logo-mini hidden"
                alt="Logo Mini" onerror="this.src='{{ asset('storage/logo/samping.png') }}'; this.onerror=null;">
        </a>
        <!-- Close button on mobile -->
        <button type="button" id="closeMobileSidebarBtn"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
            aria-label="Tutup Menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation Menu (Scrolls ONLY if items overflow screen height) -->
    <div class="flex-1 overflow-y-auto min-h-0 px-4 py-4 space-y-6 sidebar-scroll">

        <!-- Section 1: Menu Utama -->
        <div>
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 menu-heading">
                Menu Utama
            </p>
            @php
                $isDashboardActive = request()->routeIs('home');
                $isDataMasterActive = request()->is('data-master*');
                $isDataPelengkapActive = request()->is('data-pelengkap*');
                $isInformasiActive = request()->is('informasi*');
                $isPelajaranActive = request()->is('pelajaran*');
                $menuActiveClass = 'bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-md shadow-orange-600/30';
                $menuInactiveClass = 'text-slate-300 hover:text-white hover:bg-slate-800/80';
                $menuIconActiveClass = 'text-white';
                $menuIconInactiveClass = 'text-slate-400 group-hover:text-orange-400';
            @endphp
            <nav class="space-y-1">
                <!-- Dashboard -->
                <div class="relative group/tooltip">
                    <a href="{{ route('home') }}"
                        class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isDashboardActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isDashboardActive ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="menu-text">Dashboard</span>
                        </div>
                    </a>
                    <div class="sidebar-tooltip">Dashboard</div>
                </div>

                <!-- Data Master Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-data-master"
                        aria-expanded="{{ $isDataMasterActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isDataMasterActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isDataMasterActive ? $menuIconActiveClass : $menuIconInactiveClass }}" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm0 4h16M10 4v16" />
                            </svg>
                            <span class="menu-text">Data Master</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isDataMasterActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Submenu list & flyout popup container -->
                    <div id="submenu-data-master"
                        class="{{ $isDataMasterActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Data Master</span>
                        </div>
                        <a href="{{ route('tahun-ajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/tahun-ajaran*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Tahun Ajaran
                        </a>
                        <a href="{{ route('kelas.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/kelas*') || request()->is('data-master/anggota-kelas*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Kelas
                        </a>
                        <a href="{{ route('ekstrakurikuler.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/ekstrakurikuler*') || request()->is('data-master/anggota-ekstrakurikuler*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Ekstrakurikuler
                        </a>
                        <a href="{{ route('jemputan.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/jemputan*') || request()->is('data-master/anggota-jemputan*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Jemputan
                        </a>
                        <a href="{{ route('anggota-t2q.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/anggota-t2q*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Anggota T2Q
                        </a>
                        <a href="{{ route('siswa.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/siswa*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Siswa
                        </a>
                        <a href="{{ route('karyawan.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-master/karyawan*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Guru & Karyawan
                        </a>
                    </div>
                </div>

                <!-- Data Pelengkap Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-data-pelengkap"
                        aria-expanded="{{ $isDataPelengkapActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isDataPelengkapActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isDataPelengkapActive ? $menuIconActiveClass : $menuIconInactiveClass }}" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span class="menu-text">Data Pelengkap</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isDataPelengkapActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Submenu list & flyout popup container -->
                    <div id="submenu-data-pelengkap"
                        class="{{ $isDataPelengkapActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Data Pelengkap</span>
                        </div>
                        <a href="{{ route('berkebutuhan-khusus.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-pelengkap/berkebutuhan-khusus*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Berkebutuhan Khusus
                        </a>
                        <a href="{{ route('jenjang-pendidikan.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-pelengkap/jenjang-pendidikan*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Jenjang Pendidikan
                        </a>
                        <a href="{{ route('pekerjaan.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-pelengkap/pekerjaan*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Pekerjaan
                        </a>
                        <a href="{{ route('penghasilan.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-pelengkap/penghasilan*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Penghasilan
                        </a>
                        <a href="{{ route('transportasi.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('data-pelengkap/transportasi*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Transportasi
                        </a>
                    </div>
                </div>

                <!-- Informasi Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-informasi"
                        aria-expanded="{{ $isInformasiActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isInformasiActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isInformasiActive ? $menuIconActiveClass : $menuIconInactiveClass }}" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="menu-text">Informasi</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isInformasiActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-informasi"
                        class="{{ $isInformasiActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Informasi</span>
                        </div>
                        <a href="{{ route('agenda-sekolah.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('informasi/agenda-sekolah*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Agenda Sekolah
                        </a>
                        <a href="{{ route('pengumuman.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('informasi/pengumuman*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Pengumuman
                        </a>
                    </div>
                </div>

                <!-- Pelajaran Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-pelajaran"
                        aria-expanded="{{ $isPelajaranActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isPelajaranActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isPelajaranActive ? $menuIconActiveClass : $menuIconInactiveClass }}" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="menu-text">Pelajaran</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isPelajaranActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-pelajaran"
                        class="{{ $isPelajaranActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Pelajaran</span>
                        </div>
                        <a href="{{ route('kelompok-pelajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('pelajaran/kelompok-pelajaran*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Kelompok Pelajaran
                        </a>
                        <a href="{{ route('mata-pelajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('pelajaran/mata-pelajaran*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Mata Pelajaran
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Section 2: Akun & Logout (Pinned Bottom) -->
    <div class="shrink-0 p-3 border-t border-slate-800/80 bg-slate-950/40 relative group/tooltip">
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();"
            class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-all">
            <div class="flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0 text-rose-400 group-hover:text-rose-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="menu-text">Keluar Sistem</span>
            </div>
        </a>
        <div class="sidebar-tooltip">Keluar Sistem</div>
        <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</aside>
