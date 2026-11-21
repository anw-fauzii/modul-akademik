@php
    $switcherRoute = $switcherRoute ?? (request()->routeIs('wali-kelas.dashboard') ? 'wali-kelas.dashboard' : 'home');
    $switcherParams = is_array($switcherParams ?? null) ? $switcherParams : [];
    $isGuruMapelScope = (bool) ($isGuruMapelScope ?? false);
    $switcherInline = (bool) ($switcherInline ?? false);
    $compactThreshold = (int) ($compactThreshold ?? 4);
    $switcherLabel = $switcherLabel ?? ($isGuruMapelScope ? 'Kelas Diajar:' : 'Kelas Binaan Aktif:');
    $orderedClasses = $assignedClasses->values();
    $totalClasses = $orderedClasses->count();
    $useCompactPicker = $totalClasses > $compactThreshold;
    $currentClassIndex = $orderedClasses->search(fn ($item): bool => (int) $item->id === (int) $selectedKelas->id);
    $currentClassIndex = is_int($currentClassIndex) ? $currentClassIndex : 0;
    $prevClass = $currentClassIndex > 0 ? $orderedClasses->get($currentClassIndex - 1) : null;
    $nextClass = $currentClassIndex < ($totalClasses - 1) ? $orderedClasses->get($currentClassIndex + 1) : null;
    $groupedClasses = $orderedClasses
        ->groupBy(static function ($item): string {
            $tingkat = trim((string) ($item->tingkatan_kelas ?? ''));

            return $tingkat !== '' ? 'Tingkat '.$tingkat : 'Daftar Kelas';
        })
        ->sortKeys(SORT_NATURAL);
    $pickerDomId = 'wkClassPicker_'.uniqid();
@endphp

@pushOnce('css')
    <style>
        .wk-class-switcher.is-inline {
            margin-bottom: 0;
        }

        .wk-cp-wrap {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 1rem;
        }

        .wk-cp-wrap.is-inline {
            margin-bottom: 0;
        }

        .wk-cp-label {
            font-size: 0.76rem;
            font-weight: 700;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .wk-cp-count {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.48rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.68rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .wk-cp-nav {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .wk-cp-step {
            width: 32px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 0.75rem;
            text-decoration: none !important;
            background: transparent;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .wk-cp-step:hover {
            background: #f8fafc;
            color: #c2410c;
        }

        .wk-cp-step.is-disabled {
            color: #cbd5e1;
            pointer-events: none;
            background: #fcfcfd;
        }

        .wk-cp-trigger {
            border: 0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            background: #ffffff;
            height: 34px;
            padding: 0 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .wk-cp-trigger:hover {
            background: #fffaf5;
        }

        .wk-cp-tingkat {
            font-size: 0.66rem;
            font-weight: 700;
            padding: 0.12rem 0.42rem;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
        }

        .wk-cp-popover {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            z-index: 55;
            width: min(440px, 92vw);
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 16px 36px -8px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .wk-cp-popover-head {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .wk-cp-search-wrap {
            position: relative;
        }

        .wk-cp-search-wrap i {
            position: absolute;
            left: 0.68rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.72rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .wk-cp-search {
            width: 100%;
            height: 32px;
            padding: 0.25rem 0.7rem 0.25rem 1.85rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            font-size: 0.77rem;
            color: #0f172a;
            outline: none;
        }

        .wk-cp-search:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }

        .wk-cp-popover-body {
            max-height: 295px;
            overflow-y: auto;
            padding: 0.7rem 0.75rem 0.75rem;
        }

        .wk-cp-group {
            margin-bottom: 0.65rem;
        }

        .wk-cp-group:last-child {
            margin-bottom: 0;
        }

        .wk-cp-group-title {
            font-size: 0.67rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .wk-cp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 0.35rem;
        }

        .wk-cp-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.38rem 0.55rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none !important;
            text-align: center;
            transition: all 0.12s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .wk-cp-item:hover {
            border-color: #fdba74;
            background: #fff7ed;
            color: #c2410c;
        }

        .wk-cp-item.is-active {
            background: #ea580c;
            border-color: #ea580c;
            color: #ffffff;
            font-weight: 700;
        }

        .wk-cp-empty {
            padding: 1.15rem 0.5rem;
            text-align: center;
            font-size: 0.76rem;
            color: #94a3b8;
        }
    </style>
@endPushOnce

@if ($useCompactPicker)
    <div class="wk-cp-wrap {{ $switcherInline ? 'is-inline' : '' }}" data-wk-class-picker id="{{ $pickerDomId }}">
        <span class="wk-cp-label">
            <span>{{ $switcherLabel }}</span>
            <span class="wk-cp-count">{{ $currentClassIndex + 1 }}/{{ $totalClasses }} Kelas</span>
        </span>

        <div class="wk-cp-nav">
            @if ($prevClass)
                <a href="{{ route($switcherRoute, array_merge($switcherParams, ['kelas_id' => $prevClass->sqid])) }}"
                    class="wk-cp-step"
                    title="Kelas sebelumnya ({{ $prevClass->nama_kelas }})"
                    aria-label="Kelas sebelumnya ({{ $prevClass->nama_kelas }})">
                    <i class="fa fa-chevron-left"></i>
                </a>
            @else
                <span class="wk-cp-step is-disabled" aria-hidden="true">
                    <i class="fa fa-chevron-left"></i>
                </span>
            @endif

            <button type="button"
                class="wk-cp-trigger"
                data-wk-cp-toggle
                aria-expanded="false"
                aria-controls="{{ $pickerDomId }}_menu">
                <span>Kelas {{ $selectedKelas->nama_kelas }}</span>
                @if (trim((string) $selectedKelas->tingkatan_kelas) !== '')
                    <span class="wk-cp-tingkat">Tingkat {{ $selectedKelas->tingkatan_kelas }}</span>
                @endif
                <i class="fa fa-chevron-down" style="font-size: 0.68rem; color: #64748b;"></i>
            </button>

            @if ($nextClass)
                <a href="{{ route($switcherRoute, array_merge($switcherParams, ['kelas_id' => $nextClass->sqid])) }}"
                    class="wk-cp-step"
                    title="Kelas berikutnya ({{ $nextClass->nama_kelas }})"
                    aria-label="Kelas berikutnya ({{ $nextClass->nama_kelas }})">
                    <i class="fa fa-chevron-right"></i>
                </a>
            @else
                <span class="wk-cp-step is-disabled" aria-hidden="true">
                    <i class="fa fa-chevron-right"></i>
                </span>
            @endif
        </div>

        <div id="{{ $pickerDomId }}_menu" class="wk-cp-popover" data-wk-cp-menu hidden>
            <div class="wk-cp-popover-head">
                <div class="wk-cp-search-wrap">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input type="search"
                        class="wk-cp-search"
                        data-wk-cp-search
                        placeholder="Cari kelas (mis. 1A, 4B, atau 6)..."
                        aria-label="Cari kelas">
                </div>
            </div>
            <div class="wk-cp-popover-body">
                @foreach ($groupedClasses as $groupLabel => $groupItems)
                    <div class="wk-cp-group" data-wk-cp-group>
                        <div class="wk-cp-group-title">{{ $groupLabel }}</div>
                        <div class="wk-cp-grid">
                            @foreach ($groupItems as $kelasOption)
                                @php
                                    $isCurrentClass = (int) $kelasOption->id === (int) $selectedKelas->id;
                                    $itemSearch = mb_strtolower(trim($kelasOption->nama_kelas.' '.$groupLabel));
                                @endphp
                                <a href="{{ route($switcherRoute, array_merge($switcherParams, ['kelas_id' => $kelasOption->sqid])) }}"
                                    class="wk-cp-item {{ $isCurrentClass ? 'is-active' : '' }}"
                                    data-wk-cp-item="{{ $itemSearch }}"
                                    title="Kelas {{ $kelasOption->nama_kelas }}">
                                    {{ $kelasOption->nama_kelas }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="wk-cp-empty" data-wk-cp-empty hidden>
                    Kelas tidak ditemukan.
                </div>
            </div>
        </div>
    </div>

    @pushOnce('js')
        <script>
            (function () {
                document.addEventListener('click', function (event) {
                    const toggleBtn = event.target.closest('[data-wk-cp-toggle]');
                    const allPickers = document.querySelectorAll('[data-wk-class-picker]');

                    if (toggleBtn) {
                        const picker = toggleBtn.closest('[data-wk-class-picker]');
                        const menu = picker ? picker.querySelector('[data-wk-cp-menu]') : null;
                        const search = picker ? picker.querySelector('[data-wk-cp-search]') : null;
                        if (!picker || !menu) return;

                        const willOpen = menu.hidden;
                        allPickers.forEach(function (other) {
                            const otherMenu = other.querySelector('[data-wk-cp-menu]');
                            const otherBtn = other.querySelector('[data-wk-cp-toggle]');
                            if (otherMenu) otherMenu.hidden = true;
                            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                        });

                        if (willOpen) {
                            menu.hidden = false;
                            toggleBtn.setAttribute('aria-expanded', 'true');
                            if (search) {
                                window.setTimeout(function () {
                                    search.focus();
                                }, 15);
                            }
                        }
                        return;
                    }

                    if (!event.target.closest('[data-wk-cp-menu]')) {
                        allPickers.forEach(function (other) {
                            const otherMenu = other.querySelector('[data-wk-cp-menu]');
                            const otherBtn = other.querySelector('[data-wk-cp-toggle]');
                            if (otherMenu) otherMenu.hidden = true;
                            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                        });
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') return;
                    document.querySelectorAll('[data-wk-class-picker]').forEach(function (picker) {
                        const menu = picker.querySelector('[data-wk-cp-menu]');
                        const btn = picker.querySelector('[data-wk-cp-toggle]');
                        if (menu) menu.hidden = true;
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    });
                });

                document.addEventListener('input', function (event) {
                    const searchInput = event.target.closest('[data-wk-cp-search]');
                    if (!searchInput) return;
                    const picker = searchInput.closest('[data-wk-class-picker]');
                    if (!picker) return;

                    const query = searchInput.value.trim().toLowerCase();
                    let visibleTotal = 0;

                    picker.querySelectorAll('[data-wk-cp-group]').forEach(function (group) {
                        let visibleInGroup = 0;
                        group.querySelectorAll('[data-wk-cp-item]').forEach(function (item) {
                            const haystack = (item.getAttribute('data-wk-cp-item') || '').toLowerCase();
                            const matched = query === '' || haystack.includes(query);
                            item.style.display = matched ? '' : 'none';
                            if (matched) {
                                visibleInGroup++;
                                visibleTotal++;
                            }
                        });
                        group.style.display = visibleInGroup > 0 ? '' : 'none';
                    });

                    const emptyEl = picker.querySelector('[data-wk-cp-empty]');
                    if (emptyEl) {
                        emptyEl.hidden = visibleTotal > 0;
                    }
                });
            })();
        </script>
    @endPushOnce
@else
    <div class="wk-class-switcher {{ $switcherInline ? 'is-inline' : '' }}">
        <span class="small font-weight-bold text-muted mr-1">{{ $switcherLabel }}</span>
        @foreach ($assignedClasses as $kelasOption)
            @php
                $isCurrentClass = (int) $kelasOption->id === (int) $selectedKelas->id;
                $optionRole = trim((string) $kelasOption->karyawan_nipy) === trim((string) ($nipy ?? '')) ? 'Wali Kelas' : 'Pendamping';
            @endphp
            <a href="{{ route($switcherRoute, array_merge($switcherParams, ['kelas_id' => $kelasOption->sqid])) }}"
                class="wk-class-pill {{ $isCurrentClass ? 'is-active' : '' }}">
                <span>{{ $isGuruMapelScope ? 'Kelas '.$kelasOption->nama_kelas : $kelasOption->nama_kelas }}</span>
                @if (! $isGuruMapelScope)
                    <span style="opacity: 0.8; font-weight: 600;">({{ $optionRole }})</span>
                @endif
            </a>
        @endforeach
    </div>
@endif
