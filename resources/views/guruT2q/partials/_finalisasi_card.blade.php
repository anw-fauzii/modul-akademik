@php
    $t2qFinalisasi = is_array($t2qFinalisasi ?? null) ? $t2qFinalisasi : [];
    $compact = (bool) ($compact ?? false);
    $finalizeRoute = route('guru-t2q.finalisasi.finalize');
    $unlockRoute = route('guru-t2q.finalisasi.unlock');
@endphp

@if (isset($errors) && ($errors->has('finalisasi') || $errors->has('unlock_reason')))
    <div class="alert alert-danger shadow-sm border-0 mb-3 d-flex align-items-start" style="border-radius: 12px; gap: 0.65rem;">
        <i class="fa fa-lock mt-1"></i>
        <div>
            <div class="font-weight-bold">Validasi / Kunci Finalisasi T2Q</div>
            <div class="small mb-0">
                {{ $errors->first('finalisasi') ?: $errors->first('unlock_reason') }}
            </div>
        </div>
    </div>
@endif

@if ($t2qFinalisasi !== [])
    <div class="wk-surface {{ $compact ? 'mb-3' : 'mb-3' }}">
        <div class="wk-surface-head">
            <div>
                <h6 class="wk-surface-title">
                    <i class="fa fa-shield-alt text-success mr-1"></i>
                    Finalisasi Program T2Q
                </h6>
                <div class="small text-muted">
                    Kunci catatan Yaumiyah T2Q per periode (Mid Semester &amp; Semester) agar siap digunakan pada cetak rapor.
                </div>
            </div>
        </div>

        <div class="{{ $compact ? 'p-3' : 'p-3 p-md-4' }}">
            <div class="row">
                @foreach (['mid', 'semester'] as $periodeKey)
                    @php
                        $slot = $t2qFinalisasi[$periodeKey] ?? null;
                    @endphp
                    @if (is_array($slot))
                        @php
                            $isFinal = (bool) ($slot['is_final'] ?? false);
                            $isPartial = (bool) ($slot['is_partial'] ?? false);
                            $pLabel = (string) ($slot['periode_label'] ?? ($periodeKey === 'semester' ? 'Semester' : 'Mid Semester'));
                            $borderCol = $isFinal ? '#a7f3d0' : '#fde68a';
                            $bgGrad = $isFinal
                                ? 'linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%)'
                                : 'linear-gradient(135deg, #fffbeb 0%, #fffdf7 100%)';
                            $badgeBg = $isFinal ? '#059669' : ($isPartial ? '#d97706' : '#f59e0b');
                        @endphp
                        <div class="col-md-6 {{ $loop->first ? 'mb-2 mb-md-0' : '' }}">
                            <div class="p-3 d-flex flex-wrap align-items-center justify-content-between"
                                style="border-radius: 12px; gap: 0.65rem; border: 1px solid {{ $borderCol }}; background: {{ $bgGrad }};">
                                <div style="min-width: 0; flex: 1 1 200px;">
                                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.4rem;">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.84rem;">
                                            Periode {{ $pLabel }}
                                        </span>
                                        <span class="badge px-2 py-1 font-weight-bold"
                                            style="border-radius: 999px; font-size: 0.66rem; background: {{ $badgeBg }}; color: #ffffff;">
                                            <i class="fa {{ $isFinal ? 'fa-lock' : 'fa-clock' }} mr-1"></i>
                                            {{ $slot['status_label'] ?? 'DRAFT' }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mt-1" style="font-size: 0.72rem; line-height: 1.4;">
                                        @if ($isFinal)
                                            Difinalkan oleh <strong>{{ $slot['finalized_by_name'] ?: '-' }}</strong>
                                            @if (! empty($slot['finalized_at']))
                                                ({{ $slot['finalized_at']->locale('id')->translatedFormat('d M Y, H:i') }})
                                            @endif
                                        @elseif (! empty($slot['unlocked_at']))
                                            Terakhir dibuka oleh <strong>{{ $slot['unlocked_by_name'] ?: '-' }}</strong>:
                                            <em>"{{ $slot['unlock_reason'] }}"</em>
                                        @else
                                            Klik <strong>Finalkan</strong> setelah setoran T2Q periode {{ $pLabel }} lengkap diverifikasi.
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.35rem;">
                                    @if ($slot['can_finalize'] ?? false)
                                        <button type="button"
                                            class="btn btn-sm text-white font-weight-bold shadow-sm js-open-finalize-modal"
                                            style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 8px; font-size: 0.74rem; padding: 0.36rem 0.8rem;"
                                            data-action="{{ $finalizeRoute }}"
                                            data-jenis="t2q"
                                            data-ta=""
                                            data-kelas=""
                                            data-mapel=""
                                            data-ekskul=""
                                            data-periode="{{ $periodeKey }}"
                                            data-title="Finalisasi Program T2Q ({{ $pLabel }})"
                                            data-note="Catatan Yaumiyah T2Q siswa bimbingan Anda pada periode {{ $pLabel }} akan ditandai Final dan terkunci.">
                                            <i class="fa fa-lock mr-1"></i> Finalkan {{ $pLabel }}
                                        </button>
                                    @endif

                                    @if ($slot['can_unlock'] ?? false)
                                        <button type="button"
                                            class="btn btn-sm font-weight-bold shadow-sm js-open-unlock-modal"
                                            style="border-radius: 8px; background: #ffffff; color: #dc2626; border: 1px solid #fca5a5; font-size: 0.74rem; padding: 0.34rem 0.75rem;"
                                            data-action="{{ $unlockRoute }}"
                                            data-jenis="t2q"
                                            data-ta=""
                                            data-kelas=""
                                            data-mapel=""
                                            data-ekskul=""
                                            data-periode="{{ $periodeKey }}"
                                            data-title="Buka Kunci Program T2Q ({{ $pLabel }})">
                                            <i class="fa fa-unlock-alt mr-1"></i> Buka Kunci
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    @include('admin.penilaian.partials._finalisasi_modals')
@endif

