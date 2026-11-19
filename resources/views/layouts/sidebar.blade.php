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
                alt="Logo PION Yayasan Prima Insani" onerror="this.src='{{ asset('logo.png') }}'; this.onerror=null;">
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
                $authUser = Auth::user();
                $isSuperAdmin = $authUser?->hasRole('super admin') ?? false;
                $isWaliKelas = $authUser?->hasRole('wali kelas') ?? false;
                $showAdminMenus = $authUser === null || $isSuperAdmin;
                $showWaliMenus = ! $isSuperAdmin && $isWaliKelas;
                $isDashboardActive = request()->routeIs('home') || ($showWaliMenus && request()->routeIs('wali-kelas.dashboard'));
                $isWaliAnggotaActive = request()->routeIs('wali-kelas.anggota-kelas.*');
                $isWaliJadwalActive = request()->routeIs('wali-kelas.jadwal-pelajaran.*');
                $isWaliPerkembanganActive = request()->routeIs('wali-kelas.perkembangan-siswa.*');
                $isWaliPresensiActive = request()->routeIs('wali-kelas.presensi-siswa.*');
                $isWaliPenilaianActive = request()->routeIs('wali-kelas.penilaian-tp.*', 'wali-kelas.penilaian-rekap.*');
                $isWaliT2qActive = request()->routeIs('wali-kelas.t2q.*');
                $isMasterSekolahActive = request()->is('data-master/sekolah*', 'data-master/tahun-ajaran*', 'data-master/titimangsa*');
                $isMasterSdmActive = request()->is('data-master/siswa*', 'data-master/karyawan*');
                $isMasterProgramActive = request()->is(
                    'data-master/kelas*',
                    'data-master/anggota-kelas*',
                    'data-master/ekstrakurikuler*',
                    'data-master/anggota-ekstrakurikuler*',
                    'data-master/jemputan*',
                    'data-master/anggota-jemputan*'
                );
                $isMasterT2qActive = request()->is('data-master/anggota-t2q*', 'data-master/daftar-jilid*', 'data-master/target-capaian*');
                $isDataPelengkapActive = request()->is('data-pelengkap*');
                $isInformasiActive = request()->is('informasi*');
                $isPelajaranActive = request()->is('pelajaran*');
                $isTujuanPembelajaranActive = request()->is('tujuan-pembelajaran*');
                $isPenilaianActive = request()->is('penilaian*');
                $isPresensiActive = request()->is('presensi*');
                $isT2qActive = request()->is('t2q*');
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
                            <span class="menu-text">{{ $showWaliMenus ? 'Dashboard Wali Kelas' : 'Dashboard' }}</span>
                        </div>
                    </a>
                    <div class="sidebar-tooltip">{{ $showWaliMenus ? 'Dashboard Wali Kelas' : 'Dashboard' }}</div>
                </div>

                @if ($showWaliMenus)
                    @php
                        $waliSingleLinks = [
                            [
                                'label' => 'Anggota Kelas',
                                'route' => 'wali-kelas.anggota-kelas.index',
                                'active' => $isWaliAnggotaActive,
                                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                            ],
                            [
                                'label' => 'Jadwal Pelajaran',
                                'route' => 'wali-kelas.jadwal-pelajaran.index',
                                'active' => $isWaliJadwalActive,
                                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                            ],
                            [
                                'label' => 'Perkembangan Siswa',
                                'route' => 'wali-kelas.perkembangan-siswa.index',
                                'active' => $isWaliPerkembanganActive,
                                'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                            ],
                        ];
                        $waliDropdownGroups = [
                            [
                                'id' => 'submenu-wali-penilaian',
                                'label' => 'Penilaian (SD)',
                                'active' => $isWaliPenilaianActive,
                                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                                'items' => [
                                    [
                                        'label' => 'Input Nilai TP',
                                        'route' => 'wali-kelas.penilaian-tp.index',
                                        'active' => request()->routeIs('wali-kelas.penilaian-tp.*'),
                                    ],
                                    [
                                        'label' => 'Rekap & Deskripsi',
                                        'route' => 'wali-kelas.penilaian-rekap.index',
                                        'active' => request()->routeIs('wali-kelas.penilaian-rekap.*'),
                                    ],
                                ],
                            ],
                            [
                                'id' => 'submenu-wali-presensi',
                                'label' => 'Presensi Siswa',
                                'active' => $isWaliPresensiActive,
                                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                                'items' => [
                                    [
                                        'label' => 'Matriks & Log Presensi',
                                        'route' => 'wali-kelas.presensi-siswa.index',
                                        'active' => request()->routeIs('wali-kelas.presensi-siswa.index', 'wali-kelas.presensi-siswa.edit'),
                                    ],
                                    [
                                        'label' => 'Input Presensi Massal',
                                        'route' => 'wali-kelas.presensi-siswa.bulk',
                                        'active' => request()->routeIs('wali-kelas.presensi-siswa.bulk*'),
                                    ],
                                    [
                                        'label' => 'Rekap & Persentase',
                                        'route' => 'wali-kelas.presensi-siswa.rekap',
                                        'active' => request()->routeIs('wali-kelas.presensi-siswa.rekap'),
                                    ],
                                    [
                                        'label' => 'Statistik Tahunan',
                                        'route' => 'wali-kelas.presensi-siswa.tahunan',
                                        'active' => request()->routeIs('wali-kelas.presensi-siswa.tahunan'),
                                    ],
                                ],
                            ],
                            [
                                'id' => 'submenu-wali-t2q',
                                'label' => 'Yaumiyah T2Q',
                                'active' => $isWaliT2qActive,
                                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                                'items' => [
                                    [
                                        'label' => 'Matriks (Sen–Kam)',
                                        'route' => 'wali-kelas.t2q.yaumiyah-tahsin.matriks',
                                        'active' => request()->routeIs('wali-kelas.t2q.yaumiyah-tahsin.matriks', 'wali-kelas.t2q.yaumiyah-tahfizh.matriks'),
                                    ],
                                    [
                                        'label' => 'Statistik Tahun Ajaran',
                                        'route' => 'wali-kelas.t2q.yaumiyah-tahsin.statistik',
                                        'active' => request()->routeIs('wali-kelas.t2q.yaumiyah-tahsin.statistik', 'wali-kelas.t2q.yaumiyah-tahfizh.statistik'),
                                    ],
                                ],
                            ],
                        ];
                    @endphp
                    @foreach ($waliSingleLinks as $singleLink)
                        <div class="relative group/tooltip">
                            <a href="{{ route($singleLink['route']) }}"
                                class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $singleLink['active'] ? $menuActiveClass : $menuInactiveClass }}">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 shrink-0 {{ $singleLink['active'] ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $singleLink['icon'] }}" />
                                    </svg>
                                    <span class="menu-text">{{ $singleLink['label'] }}</span>
                                </div>
                            </a>
                            <div class="sidebar-tooltip">{{ $singleLink['label'] }}</div>
                        </div>
                    @endforeach

                    @foreach ($waliDropdownGroups as $dropdownGroup)
                        <div class="has-submenu relative group/submenu">
                            <button type="button" data-sidebar-toggle="{{ $dropdownGroup['id'] }}"
                                aria-expanded="{{ $dropdownGroup['active'] ? 'true' : 'false' }}"
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $dropdownGroup['active'] ? $menuActiveClass : $menuInactiveClass }}">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 shrink-0 {{ $dropdownGroup['active'] ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $dropdownGroup['icon'] }}" />
                                    </svg>
                                    <span class="menu-text">{{ $dropdownGroup['label'] }}</span>
                                </div>
                                <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $dropdownGroup['active'] ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="{{ $dropdownGroup['id'] }}"
                                class="{{ $dropdownGroup['active'] ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                                <div class="flyout-header hidden">
                                    <span>{{ $dropdownGroup['label'] }}</span>
                                </div>
                                @foreach ($dropdownGroup['items'] as $submenuItem)
                                    <a href="{{ route($submenuItem['route']) }}"
                                        class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $submenuItem['active'] ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                        {{ $submenuItem['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </nav>
        </div>

        @if ($showAdminMenus)
            @php
                $dataMasterGroups = [
                    [
                        'id' => 'submenu-master-sekolah',
                        'label' => 'Sekolah & Periode',
                        'active' => $isMasterSekolahActive,
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        'items' => [
                            [
                                'label' => 'Identitas Sekolah',
                                'route' => 'sekolah.index',
                                'active' => request()->is('data-master/sekolah*'),
                            ],
                            [
                                'label' => 'Tahun Ajaran',
                                'route' => 'tahun-ajaran.index',
                                'active' => request()->is('data-master/tahun-ajaran*'),
                            ],
                            [
                                'label' => 'Titimangsa Rapor',
                                'route' => 'titimangsa.index',
                                'active' => request()->is('data-master/titimangsa*'),
                            ],
                        ],
                    ],
                    [
                        'id' => 'submenu-master-sdm',
                        'label' => 'SDM & Siswa',
                        'active' => $isMasterSdmActive,
                        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        'items' => [
                            [
                                'label' => 'Siswa',
                                'route' => 'siswa.index',
                                'active' => request()->is('data-master/siswa*'),
                            ],
                            [
                                'label' => 'Guru & Karyawan',
                                'route' => 'karyawan.index',
                                'active' => request()->is('data-master/karyawan*'),
                            ],
                        ],
                    ],
                    [
                        'id' => 'submenu-master-program',
                        'label' => 'Kelas & Program',
                        'active' => $isMasterProgramActive,
                        'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                        'items' => [
                            [
                                'label' => 'Kelas',
                                'route' => 'kelas.index',
                                'active' => request()->is('data-master/kelas*') || request()->is('data-master/anggota-kelas*'),
                            ],
                            [
                                'label' => 'Ekstrakurikuler',
                                'route' => 'ekstrakurikuler.index',
                                'active' => request()->is('data-master/ekstrakurikuler*') || request()->is('data-master/anggota-ekstrakurikuler*'),
                            ],
                            [
                                'label' => 'Jemputan',
                                'route' => 'jemputan.index',
                                'active' => request()->is('data-master/jemputan*') || request()->is('data-master/anggota-jemputan*'),
                            ],
                        ],
                    ],
                    [
                        'id' => 'submenu-master-t2q',
                        'label' => 'T2Q',
                        'active' => $isMasterT2qActive,
                        'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        'items' => [
                            [
                                'label' => 'Anggota T2Q',
                                'route' => 'anggota-t2q.index',
                                'active' => request()->is('data-master/anggota-t2q*'),
                            ],
                            [
                                'label' => 'Daftar Jilid',
                                'route' => 'daftar-jilid.index',
                                'active' => request()->is('data-master/daftar-jilid*'),
                            ],
                            [
                                'label' => 'Target Capaian',
                                'route' => 'target-capaian.index',
                                'active' => request()->is('data-master/target-capaian*'),
                            ],
                        ],
                    ],
                ];
            @endphp

            <!-- Section 2: Data Master (Dikategorikan) -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 menu-heading">
                    Data Master
                </p>
                <nav class="space-y-1">
                    @foreach ($dataMasterGroups as $masterGroup)
                        <div class="has-submenu relative group/submenu">
                            <button type="button" data-sidebar-toggle="{{ $masterGroup['id'] }}"
                                aria-expanded="{{ $masterGroup['active'] ? 'true' : 'false' }}"
                                class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $masterGroup['active'] ? $menuActiveClass : $menuInactiveClass }}">
                                <div class="flex items-center gap-3">
                                    <svg class="h-4 w-4 shrink-0 {{ $masterGroup['active'] ? $menuIconActiveClass : $menuIconInactiveClass }}" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $masterGroup['icon'] }}" />
                                    </svg>
                                    <span class="menu-text">{{ $masterGroup['label'] }}</span>
                                </div>
                                <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $masterGroup['active'] ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="{{ $masterGroup['id'] }}"
                                class="{{ $masterGroup['active'] ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                                <div class="flyout-header hidden">
                                    <span>{{ $masterGroup['label'] }}</span>
                                </div>
                                @foreach ($masterGroup['items'] as $masterItem)
                                    <a href="{{ route($masterItem['route']) }}"
                                        class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $masterItem['active'] ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                                        {{ $masterItem['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

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
                </nav>
            </div>

            <!-- Section 3: Akademik & Informasi -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 menu-heading">
                    Akademik &amp; Informasi
                </p>
                <nav class="space-y-1">

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
                        <a href="{{ route('jam-pelajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('pelajaran/jam-pelajaran*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Jam Pelajaran
                        </a>
                        <a href="{{ route('jadwal-pelajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('pelajaran/jadwal-pelajaran*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Jadwal Pelajaran
                        </a>
                    </div>
                </div>

                <!-- Tujuan Pembelajaran Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-tujuan-pembelajaran"
                        aria-expanded="{{ $isTujuanPembelajaranActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isTujuanPembelajaranActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isTujuanPembelajaranActive ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="menu-text">Tujuan Pembelajaran</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isTujuanPembelajaranActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-tujuan-pembelajaran"
                        class="{{ $isTujuanPembelajaranActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Tujuan Pembelajaran</span>
                        </div>
                        <a href="{{ route('tujuan-pembelajaran.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('tujuan-pembelajaran/tp*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Daftar TP
                        </a>
                    </div>
                </div>

                <!-- Penilaian TP (SD) Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-penilaian"
                        aria-expanded="{{ $isPenilaianActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isPenilaianActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isPenilaianActive ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span class="menu-text">Penilaian (SD)</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isPenilaianActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-penilaian"
                        class="{{ $isPenilaianActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Penilaian (SD)</span>
                        </div>
                        <a href="{{ route('penilaian-kkm.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('penilaian/kkm*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            KKM &amp; Predikat
                        </a>
                        <a href="{{ route('penilaian-tp.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('penilaian/nilai-tp*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Input Nilai TP
                        </a>
                        <a href="{{ route('penilaian-rekap.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('penilaian/rekap*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Rekap &amp; Deskripsi
                        </a>
                        <a href="{{ route('penilaian-rapor.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->is('penilaian/rapor*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Cetak Rapor PDF
                        </a>
                    </div>
                </div>

                <!-- Presensi Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-presensi"
                        aria-expanded="{{ $isPresensiActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isPresensiActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isPresensiActive ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span class="menu-text">Presensi Siswa</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isPresensiActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-presensi"
                        class="{{ $isPresensiActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Presensi Siswa</span>
                        </div>
                        <a href="{{ route('presensi-siswa.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('presensi-siswa.index', 'presensi-siswa.edit') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Matriks &amp; Log Presensi
                        </a>
                        <a href="{{ route('presensi-siswa.bulk') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('presensi-siswa.bulk*') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Input Presensi Massal
                        </a>
                        <a href="{{ route('presensi-siswa.rekap') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('presensi-siswa.rekap') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Rekap &amp; Persentase
                        </a>
                        <a href="{{ route('presensi-siswa.tahunan') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('presensi-siswa.tahunan') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Statistik Tahunan
                        </a>
                    </div>
                </div>

                <!-- T2Q Yaumiyah Dropdown -->
                <div class="has-submenu relative group/submenu">
                    <button type="button" data-sidebar-toggle="submenu-t2q"
                        aria-expanded="{{ $isT2qActive ? 'true' : 'false' }}"
                        class="w-full group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ $isT2qActive ? $menuActiveClass : $menuInactiveClass }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $isT2qActive ? $menuIconActiveClass : $menuIconInactiveClass }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="menu-text">Yaumiyah T2Q</span>
                        </div>
                        <svg class="menu-chevron h-3.5 w-3.5 transition-transform duration-200 {{ $isT2qActive ? 'text-white rotate-180' : 'text-slate-400 group-hover:text-white' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="submenu-t2q"
                        class="{{ $isT2qActive ? '' : 'hidden' }} submenu-flyout mt-1 space-y-1 pl-4 border-l border-slate-800 ml-5 py-1">
                        <div class="flyout-header hidden">
                            <span>Yaumiyah T2Q</span>
                        </div>
                        <a href="{{ route('t2q.yaumiyah-tahsin.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('t2q.yaumiyah-tahsin.index') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Yaumiyah Tahsin
                        </a>
                        <a href="{{ route('t2q.yaumiyah-tahfizh.index') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('t2q.yaumiyah-tahfizh.index') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Yaumiyah Tahfizh
                        </a>
                        <a href="{{ route('t2q.yaumiyah-tahsin.matriks') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('t2q.yaumiyah-*.matriks') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Matriks (Sen–Kam)
                        </a>
                        <a href="{{ route('t2q.yaumiyah-tahsin.statistik') }}"
                            class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('t2q.yaumiyah-*.statistik') ? 'text-orange-400 bg-orange-500/10 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Statistik Tahun Ajaran
                        </a>
                    </div>
                </div>
                </nav>
            </div>
        @endif
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
