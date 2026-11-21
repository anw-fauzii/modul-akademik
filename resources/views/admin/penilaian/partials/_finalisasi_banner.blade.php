@php
    $fnIsFinal = (bool) ($finalisasi['is_final'] ?? false);
    $fnPeriode = (string) ($finalisasi['periode'] ?? 'mid');
    $fnPeriodeLabel = (string) ($finalisasi['periode_label'] ?? ($fnPeriode === 'semester' ? 'Semester' : 'Mid Semester'));
    $fnRoutePrefix = $routePrefix ?? 'penilaian-finalisasi';
    $fnCanFinalize = (bool) ($finalisasi['can_finalize'] ?? false);
    $fnCanUnlock = (bool) ($finalisasi['can_unlock'] ?? false);
    $fnTaId = (int) ($finalisasi['tahun_ajaran_id'] ?? 0);
    $fnKelasId = $finalisasi['kelas_id'] ?? null;
    $fnMapelId = $finalisasi['mata_pelajaran_id'] ?? null;
    $fnEkskulId = $finalisasi['ekstrakurikuler_id'] ?? null;
    $fnTaSqid = $fnTaId > 0 ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\TahunAjaran::class, $fnTaId) : '';
    $fnKelasSqid = $fnKelasId ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\Kelas::class, (int) $fnKelasId) : '';
    $fnMapelSqid = $fnMapelId ? \App\Support\Sqid::encodeFor(\App\Models\Admin\Pelajaran\MataPelajaran::class, (int) $fnMapelId) : '';
    $fnEkskulSqid = $fnEkskulId ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\Ekstrakurikuler::class, (int) $fnEkskulId) : '';
    $fnFinalizeUrl = route($fnRoutePrefix.'.finalize');
    $fnUnlockUrl = route($fnRoutePrefix.'.unlock');
    $fnTitle = $componentTitle ?? ($finalisasi['jenis_label'] ?? 'Komponen Penilaian');
    $fnNote = $summaryNote ?? 'Setelah difinalkan, pengeditan data pada periode ini dikunci otomatis sampai dibuka kunci kembali.';
    $fnCompact = (bool) ($compact ?? false);
@endphp

@if (isset($errors) && ($errors->has('finalisasi') || $errors->has('unlock_reason')))
    <div class="alert alert-danger shadow-sm border-0 mb-3 d-flex align-items-start" style="border-radius: 12px; gap: 0.65rem;">
        <i class="fa fa-lock mt-1"></i>
        <div>
            <div class="font-weight-bold">Perubahan Diblokir / Validasi Finalisasi</div>
            <div class="small mb-0">
                {{ $errors->first('finalisasi') ?: $errors->first('unlock_reason') }}
            </div>
        </div>
    </div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between {{ $fnCompact ? 'px-3 py-2' : 'p-3' }} mb-3 shadow-sm"
    style="border-radius: 14px; gap: 0.75rem 1rem; border: 1px solid {{ $fnIsFinal ? '#a7f3d0' : '#fde68a' }}; background: {{ $fnIsFinal ? 'linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%)' : 'linear-gradient(135deg, #fffbeb 0%, #fffdf7 100%)' }};">
    <div class="d-flex align-items-start" style="gap: 0.75rem; min-width: 0; flex: 1 1 300px;">
        <div class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
            style="width: {{ $fnCompact ? '34px' : '40px' }}; height: {{ $fnCompact ? '34px' : '40px' }}; border-radius: 11px; background: {{ $fnIsFinal ? '#d1fae5' : '#fef3c7' }}; color: {{ $fnIsFinal ? '#047857' : '#b45309' }};">
            <i class="fa {{ $fnIsFinal ? 'fa-lock' : 'fa-unlock-alt' }}"></i>
        </div>
        <div style="min-width: 0;">
            <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                <span class="font-weight-bold text-dark" style="font-size: {{ $fnCompact ? '0.82rem' : '0.88rem' }};">
                    {{ $fnTitle }}
                </span>
                <span class="badge px-2 py-1 font-weight-bold"
                    style="border-radius: 999px; font-size: 0.68rem; background: {{ $fnIsFinal ? '#059669' : '#d97706' }}; color: #ffffff;">
                    <i class="fa {{ $fnIsFinal ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>
                    {{ $fnIsFinal ? 'FINAL & TERKUNCI' : 'DRAFT (BELUM FINAL)' }}
                </span>
                <span class="badge badge-light border text-dark font-weight-semibold" style="border-radius: 999px; font-size: 0.68rem;">
                    Periode {{ $fnPeriodeLabel }}
                </span>
            </div>

            <div class="small text-muted mt-1" style="font-size: 0.75rem; line-height: 1.4;">
                @if ($fnIsFinal)
                    Difinalkan oleh <strong>{{ $finalisasi['finalized_by_name'] ?: '-' }}</strong>
                    @if ($finalisasi['finalized_at'])
                        pada <strong>{{ $finalisasi['finalized_at']->locale('id')->translatedFormat('d M Y, H:i') }}</strong>
                    @endif
                    &bull; Data terkunci aman untuk cetak rapor.
                @elseif (! empty($finalisasi['unlocked_at']))
                    Terakhir dibuka kunci oleh <strong>{{ $finalisasi['unlocked_by_name'] ?: '-' }}</strong>
                    pada <strong>{{ $finalisasi['unlocked_at']->locale('id')->translatedFormat('d M Y, H:i') }}</strong>
                    (<em>"{{ $finalisasi['unlock_reason'] }}"</em>).
                @else
                    Nilai periode <strong>{{ $fnPeriodeLabel }}</strong> masih berstatus Draft. Segera finalisasi setelah seluruh nilai lengkap dan diverifikasi.
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
        @if ($fnCanFinalize)
            <button type="button"
                class="btn btn-sm text-white font-weight-bold shadow-sm js-open-finalize-modal"
                style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 9px; padding: 0.42rem 0.9rem;"
                data-action="{{ $fnFinalizeUrl }}"
                data-jenis="{{ $finalisasi['jenis_komponen'] }}"
                data-ta="{{ $fnTaSqid }}"
                data-kelas="{{ $fnKelasSqid }}"
                data-mapel="{{ $fnMapelSqid }}"
                data-ekskul="{{ $fnEkskulSqid }}"
                data-periode="{{ $fnPeriode }}"
                data-title="{{ $fnTitle }} ({{ $fnPeriodeLabel }})"
                data-note="{{ $fnNote }}">
                <i class="fa fa-lock mr-1"></i> Finalkan {{ $fnPeriodeLabel }}
            </button>
        @elseif ($fnCanUnlock)
            <button type="button"
                class="btn btn-sm font-weight-bold shadow-sm js-open-unlock-modal"
                style="border-radius: 9px; background: #ffffff; color: #dc2626; border: 1px solid #fca5a5; padding: 0.4rem 0.85rem;"
                data-action="{{ $fnUnlockUrl }}"
                data-jenis="{{ $finalisasi['jenis_komponen'] }}"
                data-ta="{{ $fnTaSqid }}"
                data-kelas="{{ $fnKelasSqid }}"
                data-mapel="{{ $fnMapelSqid }}"
                data-ekskul="{{ $fnEkskulSqid }}"
                data-periode="{{ $fnPeriode }}"
                data-title="{{ $fnTitle }} ({{ $fnPeriodeLabel }})">
                <i class="fa fa-unlock-alt mr-1"></i> Buka Kunci
            </button>
        @elseif ($fnIsFinal)
            <span class="badge badge-light border px-2 py-1 text-muted" style="border-radius: 8px; font-size: 0.72rem;">
                <i class="fa fa-shield-alt mr-1 text-success"></i> Hanya guru pengampu terkait / Admin yang dapat membuka kunci
            </span>
        @else
            <span class="badge badge-light border px-2 py-1 text-muted" style="border-radius: 8px; font-size: 0.72rem;">
                <i class="fa fa-eye mr-1"></i> Menunggu finalisasi oleh penanggung jawab komponen
            </span>
        @endif
    </div>
</div>

@include('admin.penilaian.partials._finalisasi_modals')
