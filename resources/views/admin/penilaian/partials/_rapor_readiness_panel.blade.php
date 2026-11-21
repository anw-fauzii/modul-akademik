@php
    $rdReady = (bool) ($readiness['is_ready'] ?? false);
    $rdTotal = (int) ($readiness['total_components'] ?? 0);
    $rdFinalized = (int) ($readiness['finalized_count'] ?? 0);
    $rdPending = (int) ($readiness['pending_count'] ?? 0);
    $rdPct = (int) ($readiness['progress_pct'] ?? 0);
    $rdPeriodeLabel = (string) ($readiness['periode_label'] ?? ($periode === 'semester' ? 'Semester' : 'Mid Semester'));
    $rdRoutePrefix = $routePrefix ?? 'penilaian-finalisasi';
    $rdComponents = $readiness['components'] ?? [];
    $rdCollapseId = $collapseId ?? 'raporReadinessChecklistBody';
    $rdExpanded = isset($defaultExpanded) ? (bool) $defaultExpanded : ! $rdReady;
@endphp

<div class="main-card card mb-3 shadow-sm border-0"
    style="border-radius: 14px; border: 1px solid {{ $rdReady ? '#a7f3d0' : '#fecdd3' }} !important; overflow: hidden;">
    <div class="card-body py-3 px-3 px-md-4 d-flex flex-wrap align-items-center justify-content-between"
        style="gap: 0.75rem 1rem; background: {{ $rdReady ? 'linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%)' : 'linear-gradient(135deg, #fff1f2 0%, #fffaf5 60%, #ffffff 100%)' }};">
        <div class="d-flex align-items-start" style="gap: 0.75rem; flex: 1 1 320px;">
            <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
                style="width: 40px; height: 40px; border-radius: 11px; background: {{ $rdReady ? '#d1fae5' : '#ffe4e6' }}; color: {{ $rdReady ? '#047857' : '#be123c' }};">
                <i class="fa {{ $rdReady ? 'fa-shield-alt' : 'fa-ban' }}"></i>
            </div>
            <div>
                <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                    <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                        Kesiapan Cetak Nilai Rapor ({{ $rdPeriodeLabel }})
                    </span>
                    <span class="badge px-2 py-1 font-weight-bold"
                        style="border-radius: 999px; font-size: 0.69rem; background: {{ $rdReady ? '#059669' : '#e11d48' }}; color: #ffffff;">
                        {{ $rdFinalized }}/{{ $rdTotal }} Komponen Final ({{ $rdPct }}%)
                    </span>
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.76rem;">
                    @if ($rdReady)
                        Seluruh komponen wajib Opsi C (Mapel terjadwal/bernilai, Ekskul, T2Q, dan Presensi) telah difinalkan &amp; siap dicetak.
                    @else
                        Cetak PDF Nilai Rapor <strong>diblokir sementara</strong> sampai {{ $rdPending }} komponen yang masih <strong>Draft</strong> difinalkan oleh masing-masing penanggung jawab.
                    @endif
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-light border font-weight-semibold js-toggle-readiness-table"
            data-target="{{ $rdCollapseId }}"
            style="border-radius: 9px; font-size: 0.75rem;">
            <i class="fa fa-list-check mr-1 text-primary"></i>
            <span>{{ $rdReady ? 'Lihat Checklist' : 'Lihat Kekurangan ('.$rdPending.')' }}</span>
        </button>
    </div>

    <div id="{{ $rdCollapseId }}" style="{{ $rdExpanded ? '' : 'display: none;' }} border-top: 1px solid #e2e8f0; background: #ffffff;">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size: 0.78rem;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th class="pl-3" style="width: 15%;">Kategori</th>
                        <th>Komponen Penilaian</th>
                        <th>Penanggung Jawab</th>
                        <th class="text-center" style="width: 14%;">Status</th>
                        <th class="text-right pr-3" style="width: 20%;">Aksi / Audit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rdComponents as $comp)
                        @php
                            $cIsFinal = (bool) ($comp['is_final'] ?? false);
                            $cOwned = (bool) ($comp['is_owned_by_user'] ?? false);
                            $cPlaceholder = (bool) ($comp['is_placeholder'] ?? false);
                            $cTaSqid = ! empty($comp['tahun_ajaran_id']) ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\TahunAjaran::class, (int) $comp['tahun_ajaran_id']) : '';
                            $cKelasSqid = ! empty($comp['kelas_id']) ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\Kelas::class, (int) $comp['kelas_id']) : '';
                            $cMapelSqid = ! empty($comp['mata_pelajaran_id']) ? \App\Support\Sqid::encodeFor(\App\Models\Admin\Pelajaran\MataPelajaran::class, (int) $comp['mata_pelajaran_id']) : '';
                            $cEkskulSqid = ! empty($comp['ekstrakurikuler_id']) ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\Ekstrakurikuler::class, (int) $comp['ekstrakurikuler_id']) : '';
                        @endphp
                        <tr>
                            <td class="pl-3 align-middle">
                                <span class="badge badge-light border font-weight-semibold text-dark" style="font-size: 0.69rem;">
                                    {{ $comp['jenis_label'] }}
                                </span>
                            </td>
                            <td class="align-middle font-weight-semibold text-dark">
                                {{ $comp['nama'] }}
                            </td>
                            <td class="align-middle text-muted">
                                {{ $comp['owner_label'] }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($cIsFinal)
                                    <span class="badge px-2 py-1 font-weight-bold" style="background: #d1fae5; color: #047857; border-radius: 999px; font-size: 0.68rem;">
                                        <i class="fa fa-lock mr-1"></i> Final
                                    </span>
                                @else
                                    <span class="badge px-2 py-1 font-weight-bold" style="background: #fef3c7; color: #b45309; border-radius: 999px; font-size: 0.68rem;">
                                        <i class="fa fa-clock mr-1"></i> Draft
                                    </span>
                                @endif
                            </td>
                            <td class="text-right pr-3 align-middle text-nowrap">
                                @if ($cPlaceholder)
                                    <span class="text-muted small font-italic">Jadwalkan / nilai mapel dulu</span>
                                @elseif ($cOwned && ! $cIsFinal)
                                    <button type="button"
                                        class="btn btn-xs btn-sm text-white font-weight-bold js-open-finalize-modal"
                                        style="background: #059669; border-radius: 7px; font-size: 0.7rem; padding: 0.22rem 0.6rem;"
                                        data-action="{{ route($rdRoutePrefix.'.finalize') }}"
                                        data-jenis="{{ $comp['jenis_komponen'] }}"
                                        data-ta="{{ $cTaSqid }}"
                                        data-kelas="{{ $cKelasSqid }}"
                                        data-mapel="{{ $cMapelSqid }}"
                                        data-ekskul="{{ $cEkskulSqid }}"
                                        data-periode="{{ $comp['periode'] }}"
                                        data-title="{{ $comp['nama'] }} ({{ $rdPeriodeLabel }})"
                                        data-note="Komponen ini akan ditandai Final dan terkunci pada periode {{ $rdPeriodeLabel }}.">
                                        <i class="fa fa-lock mr-1"></i> Finalkan
                                    </button>
                                @elseif ($cOwned && $cIsFinal)
                                    <button type="button"
                                        class="btn btn-sm font-weight-semibold js-open-unlock-modal"
                                        style="border-radius: 7px; font-size: 0.7rem; padding: 0.2rem 0.55rem; background: #ffffff; color: #dc2626; border: 1px solid #fca5a5;"
                                        data-action="{{ route($rdRoutePrefix.'.unlock') }}"
                                        data-jenis="{{ $comp['jenis_komponen'] }}"
                                        data-ta="{{ $cTaSqid }}"
                                        data-kelas="{{ $cKelasSqid }}"
                                        data-mapel="{{ $cMapelSqid }}"
                                        data-ekskul="{{ $cEkskulSqid }}"
                                        data-periode="{{ $comp['periode'] }}"
                                        data-title="{{ $comp['nama'] }} ({{ $rdPeriodeLabel }})">
                                        <i class="fa fa-unlock-alt mr-1"></i> Buka Kunci
                                    </button>
                                @elseif ($cIsFinal)
                                    <span class="small text-muted" title="Difinalkan oleh {{ $comp['finalized_by_name'] ?? '-' }}">
                                        <i class="fa fa-check text-success mr-1"></i>{{ $comp['finalized_by_name'] ?: 'Final' }}
                                    </span>
                                @else
                                    <span class="small text-muted font-italic">Menunggu penanggung jawab</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.penilaian.partials._finalisasi_modals')

@once
    @push('js')
        <script>
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-toggle-readiness-table');
                if (!btn) return;
                const target = document.getElementById(btn.dataset.target);
                if (!target) return;
                target.style.display = target.style.display === 'none' ? '' : 'none';
            });
        </script>
    @endpush
@endonce

