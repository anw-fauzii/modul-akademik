<header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200/80 bg-white/90 backdrop-blur-md px-4 sm:px-6 transition-all duration-200">
    <!-- Left Section: Mobile & Desktop Sidebar Toggles + Breadcrumb/App Name -->
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Open Button -->
        <button type="button" id="mobileSidebarToggle" aria-label="Buka Menu"
            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/30 lg:hidden transition-colors">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Desktop Sidebar Collapse Toggle -->
        <button type="button" id="desktopSidebarToggle" aria-label="Toggle Sidebar"
            class="hidden lg:flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500/30 transition-colors">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16" />
            </svg>
        </button>

        <!-- Context Badge / Title -->
        <div class="hidden sm:flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 border border-orange-200/60">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                SIPIT AKADEMIK
            </span>
            <span class="text-xs text-slate-400 font-medium">|</span>
            <span class="text-xs font-medium text-slate-500">Yayasan Prima Insani</span>
        </div>
    </div>

    <!-- Right Section: Quick Actions & User Dropdown -->
    <div class="flex items-center gap-3">
        <!-- Quick Scan QR Button (Shortcut) -->

        <!-- User Profile Dropdown Container -->
        <div class="relative">
            <button type="button" id="userMenuButton"
                class="flex items-center gap-3 rounded-xl p-1.5 text-left hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500/30"
                aria-expanded="false" aria-haspopup="true">
                <div class="relative">
                    @php
                        $authUser = Auth::user();
                        $navbarFotoUrl = $authUser?->karyawan?->foto_url
                            ?? ($authUser?->foto ? asset('storage/' . $authUser->foto) : null);
                        $fallbackFotoUrl = asset('storage/logo/user.png');
                    @endphp
                    @if ($navbarFotoUrl)
                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-orange-500/20"
                            src="{{ $navbarFotoUrl }}" alt="Foto {{ $authUser->name }}"
                            onerror="this.onerror=null; this.src='{{ $fallbackFotoUrl }}';">
                    @else
                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-orange-500/20"
                            src="{{ $fallbackFotoUrl }}" alt="Foto User">
                    @endif
                    <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </div>
                <div class="hidden md:block text-left">
                    <p class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[140px]">
                        {{ Auth::user()->name ?? 'Administrator' }}
                    </p>
                    <p class="text-[11px] text-slate-500 leading-tight truncate max-w-[140px]">
                        {{ Auth::user()->email ?? 'admin@primainsani.sch.id' }}
                    </p>
                </div>
                <svg class="hidden md:block h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="userMenuDropdown"
                class="hidden absolute right-0 mt-2 w-56 origin-top-right rounded-2xl bg-white p-2 shadow-xl ring-1 ring-slate-900/10 focus:outline-none z-50 transition-all">
                <div class="px-3 py-2 border-b border-slate-100 mb-1">
                    <p class="text-xs font-bold text-slate-800">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <a href="{{ route('home') }}"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <div class="my-1 border-t border-slate-100"></div>
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                    <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Keluar / Logout
                </a>
                <form id="navbar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>
