@php
    $summaryData = $statusSummary ?? [];
    $totalRecords = isset($totalCatatan) ? (int) $totalCatatan : null;
    $metaOverrides = $statusMeta ?? [];
    $rateList = $rateCards ?? [];
    $isCompactBulk = (bool) ($compact ?? false);
    $showStatusGrid = (bool) ($showStatusCards ?? true);
    $rateGridExtraClass = trim((string) ($rateGridClass ?? ''));
    $batasJam = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;

    $statusDefinitions = [
        'hadir' => [
            'label' => 'Hadir',
            'tone' => 'emerald',
            'icon' => 'fa fa-check-circle',
            'default_subtext' => 'Tepat waktu',
        ],
        'terlambat' => [
            'label' => 'Terlambat',
            'tone' => 'amber',
            'icon' => 'fa fa-clock',
            'default_subtext' => 'Lewat '.$batasJam,
        ],
        'izin' => [
            'label' => 'Izin',
            'tone' => 'sky',
            'icon' => 'fa fa-envelope',
            'default_subtext' => 'Dengan keterangan',
        ],
        'sakit' => [
            'label' => 'Sakit',
            'tone' => 'indigo',
            'icon' => 'fa fa-notes-medical',
            'default_subtext' => 'Info sakit',
        ],
        'alpa' => [
            'label' => 'Alpa',
            'tone' => 'rose',
            'icon' => 'fa fa-times-circle',
            'default_subtext' => 'Tanpa keterangan',
        ],
    ];
@endphp

@if ($showStatusGrid)
    <div class="presensi-kpi-grid {{ $isCompactBulk ? 'presensi-kpi-grid--bulk' : '' }}">
        @foreach ($statusDefinitions as $key => $cfg)
            @php
                $countValue = (int) ($summaryData[$key] ?? 0);
                $override = $metaOverrides[$key] ?? [];
                $sharePct = $override['share_pct']
                    ?? ($totalRecords !== null && $totalRecords > 0 ? round(($countValue / $totalRecords) * 100, 1) : null);
                $barWidth = $sharePct !== null ? min(100, max(0, (float) $sharePct)) : ($isCompactBulk ? 100 : 0);
                $subtext = $override['subtext'] ?? $cfg['default_subtext'];
                $valueId = $override['value_id'] ?? null;
            @endphp
            <div class="presensi-kpi-card presensi-kpi--{{ $cfg['tone'] }}">
                <div class="presensi-kpi-top">
                    <div class="presensi-kpi-meta">
                        <div class="presensi-kpi-label">
                            <span class="presensi-kpi-dot"></span>
                            <span>{{ $cfg['label'] }}</span>
                        </div>
                        <div class="presensi-kpi-value" @if ($valueId) id="{{ $valueId }}" @endif>{{ $countValue }}</div>
                    </div>
                    <div class="presensi-kpi-icon" aria-hidden="true">
                        <i class="{{ $cfg['icon'] }}"></i>
                    </div>
                </div>

                <div class="presensi-kpi-bottom">
                    <div class="presensi-kpi-subrow">
                        <span class="presensi-kpi-subtext">{{ $subtext }}</span>
                        @if ($sharePct !== null)
                            <span class="presensi-kpi-share">{{ $sharePct }}%</span>
                        @endif
                    </div>
                    <div class="presensi-kpi-track">
                        <div class="presensi-kpi-fill" style="width: {{ $barWidth }}%;"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if (count($rateList) > 0)
    <div class="presensi-rate-grid {{ $rateGridExtraClass }}">
        @foreach ($rateList as $rate)
            @php
                $rateTone = $rate['tone'] ?? 'orange';
                $ratePct = min(100, max(0, (float) ($rate['percent'] ?? $rate['value'] ?? 0)));
            @endphp
            <div class="presensi-rate-card presensi-rate--{{ $rateTone }}">
                <div class="presensi-rate-main">
                    <div class="presensi-rate-left">
                        <div class="presensi-rate-heading">
                            <span class="presensi-rate-title">{{ $rate['label'] }}</span>
                            @if (! empty($rate['badge']))
                                <span class="presensi-rate-badge">{{ $rate['badge'] }}</span>
                            @endif
                        </div>
                        <div class="presensi-rate-value">{{ $rate['value'] }}%</div>
                        @if (! empty($rate['subtitle']))
                            <div class="presensi-rate-sub">{{ $rate['subtitle'] }}</div>
                        @endif
                    </div>
                    <div class="presensi-rate-icon" aria-hidden="true">
                        <i class="{{ $rate['icon'] ?? 'fa fa-chart-line' }}"></i>
                    </div>
                </div>
                <div class="presensi-rate-track">
                    <div class="presensi-rate-fill" style="width: {{ $ratePct }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif

