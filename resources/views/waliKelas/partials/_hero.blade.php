@php
    $heroDate = $todayDate ?? \Carbon\Carbon::now();
    $heroBadgeText = $heroBadgeText ?? ($isEligible ? strtoupper((string) $peranDiKelas) : 'PANEL WALI KELAS');
    $heroTitle = $heroTitle ?? '';
    $heroSubtitle = $heroSubtitle ?? null;
    $heroFallbackSubtitle = $heroFallbackSubtitle ?? 'Panel Wali Kelas terhubung otomatis dengan penugasan Wali Kelas atau Guru Pendamping pada Tahun Ajaran aktif.';
    $heroActionUrl = $heroActionUrl ?? null;
    $heroActionLabel = $heroActionLabel ?? null;
    $heroActionIcon = $heroActionIcon ?? 'fa-arrow-right';
@endphp

<div class="wk-hero">
    <div class="wk-hero-glow"></div>

    <div class="row align-items-center position-relative" style="z-index: 1;">
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge text-white px-2 py-1 font-weight-bold"
                    style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                    <i class="fa fa-chalkboard-teacher mr-1"></i>
                    {{ $heroBadgeText }}
                </span>
                <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                    <i class="fa fa-calendar-alt mr-1"></i>
                    {{ $heroDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                </span>
            </div>

            <h3 class="font-weight-bold text-white mb-2">
                {{ $heroTitle }}
            </h3>

            @if ($isEligible && $selectedKelas)
                <p class="mb-3" style="color: rgba(255, 255, 255, 0.78); max-width: 640px; font-size: 0.86rem; line-height: 1.5;">
                    @if ($heroSubtitle)
                        {{ $heroSubtitle }}
                    @else
                        Anda terdaftar sebagai <strong>{{ $peranDiKelas }}</strong> untuk kelas
                        <strong>{{ $selectedKelas->nama_kelas }}</strong>
                        (Jenjang {{ $selectedKelas->jenjang_kelas }} &bull; Tingkat {{ $selectedKelas->tingkatan_kelas }})
                        pada tahun ajaran aktif.
                    @endif
                </p>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge px-3 py-2"
                        style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #ffffff; font-size: 0.74rem;">
                        <i class="fa fa-graduation-cap text-warning mr-1"></i>
                        {{ $activeTahunAjaran->full_label }}
                    </span>
                    <span class="badge px-3 py-2"
                        style="background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.35); color: #ecfdf5; font-size: 0.74rem;">
                        <i class="fa fa-door-open mr-1"></i>
                        Kelas {{ $selectedKelas->nama_kelas }}
                    </span>
                    @if ($heroActionUrl && $heroActionLabel)
                        <a href="{{ $heroActionUrl }}" class="badge px-3 py-2 text-decoration-none"
                            style="background: rgba(234, 88, 12, 0.9); border: 1px solid rgba(251, 146, 60, 0.65); color: #ffffff; font-size: 0.74rem;">
                            <i class="fa {{ $heroActionIcon }} mr-1"></i>
                            {{ $heroActionLabel }}
                        </a>
                    @endif
                </div>
            @else
                <p class="mb-2" style="color: rgba(255, 255, 255, 0.76); max-width: 640px; font-size: 0.86rem;">
                    {{ $heroFallbackSubtitle }}
                </p>
                @if ($activeTahunAjaran)
                    <span class="badge px-3 py-2"
                        style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 0.74rem;">
                        <i class="fa fa-graduation-cap text-warning mr-1"></i>
                        Tahun Ajaran Aktif: {{ $activeTahunAjaran->full_label }}
                    </span>
                @endif
            @endif
        </div>

        @if ($isEligible && $selectedKelas)
            <div class="col-lg-4">
                <div class="wk-team-card">
                    <div class="text-uppercase font-weight-bold mb-2"
                        style="font-size: 0.68rem; letter-spacing: 0.05em; color: #fb923c;">
                        Tim Pengelola Kelas {{ $selectedKelas->nama_kelas }}
                    </div>
                    <div class="mb-2">
                        <div style="font-size: 0.68rem; color: rgba(255, 255, 255, 0.62);">Wali Kelas</div>
                        <div class="font-weight-bold text-white" style="font-size: 0.84rem;">
                            {{ $selectedKelas->waliKelas?->nama_gelar ?? ($selectedKelas->karyawan_nipy ?: '-') }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.68rem; color: rgba(255, 255, 255, 0.62);">Guru Pendamping</div>
                        <div class="font-weight-semibold" style="font-size: 0.82rem; color: rgba(255, 255, 255, 0.9);">
                            {{ $selectedKelas->pendamping?->nama_gelar ?? ($selectedKelas->pendamping_nipy ?: 'Belum ditetapkan') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

