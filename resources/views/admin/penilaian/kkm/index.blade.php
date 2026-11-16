@extends('layouts.appui')

@section('title')
    <title>KKM &amp; Rentang Predikat (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .kkm-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .kkm-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .kkm-filter-grid {
                    grid-template-columns: 1.3fr 1.4fr 0.9fr auto;
                }
            }

            .kkm-pred-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
            }

            .kkm-pred-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.2rem 0.55rem;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 600;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
                font-variant-numeric: tabular-nums;
            }

            .kkm-pred-chip.is-fail {
                background: #fef2f2;
                border-color: #fecaca;
                color: #b91c1c;
            }
        </style>
    @endpush

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-ribbon" style="color: white;"></i>
                    </div>
                    <div>
                        KKM &amp; Rentang Predikat (SD)
                        <div class="page-title-subheading">
                            Atur nilai batas ketuntasan minimal (KKM) dan rentang predikat nilai rapor per jenjang kelas SD.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('penilaian-tp.index') }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-th mr-1"></i> Input Nilai TP
                    </a>
                    <a href="{{ route('penilaian-kkm.create', array_filter($filters ?? [])) }}"
                        class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-plus mr-1"></i> Tambah KKM
                    </a>
                </div>
            </div>
        </div>

        @php
            $filters = $filters ?? ['tahun_ajaran_id' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''];
            $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
            $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
            $tingkatanOptions = $tingkatanOptions ?? \App\Models\Admin\Penilaian\Kkm::SD_TINGKATAN_OPTIONS;
        @endphp

        <div class="kkm-filter-bar">
            <form method="GET" action="{{ route('penilaian-kkm.index') }}">
                <div class="kkm-filter-grid">
                    <div>
                        <label for="filter_ta" class="small font-weight-semibold text-muted mb-1">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="filter_ta" class="form-control form-control-sm">
                            <option value="all" @selected($filters['tahun_ajaran_id'] === '')>Semua Tahun Ajaran</option>
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}" @selected((string) $filters['tahun_ajaran_id'] === (string) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_mp" class="small font-weight-semibold text-muted mb-1">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="filter_mp" class="form-control form-control-sm">
                            <option value="">Semua Mata Pelajaran SD</option>
                            @foreach ($mataPelajaranOptions as $mp)
                                <option value="{{ $mp->id }}" @selected((string) $filters['mata_pelajaran_id'] === (string) $mp->id)>
                                    [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter_tingkat" class="small font-weight-semibold text-muted mb-1">Tingkatan Kelas</label>
                        <select name="tingkatan_kelas" id="filter_tingkat" class="form-control form-control-sm">
                            <option value="">Semua Kelas SD</option>
                            @foreach ($tingkatanOptions as $tingkat)
                                <option value="{{ $tingkat }}" @selected($filters['tingkatan_kelas'] === $tingkat)>
                                    Kelas {{ $tingkat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm text-white"
                            style="background: #ea580c; border-radius: 8px; height: 36px; padding: 0 1rem;">
                            <i class="fa fa-filter mr-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <div class="table-responsive">
                    <table style="width: 100%;" id="myTable3" data-empty-table="Belum ada pengaturan KKM SD."
                        class="table table-hover table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Tahun Ajaran</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center" style="width: 105px;">Tingkat</th>
                                <th class="text-center" style="width: 85px;">KKM</th>
                                <th>Rentang Predikat</th>
                                <th class="text-center" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kkmList as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item->tahunAjaran?->full_label ?? '-' }}</td>
                                    <td>
                                        <span class="font-weight-semibold">{{ $item->mataPelajaran?->nama_mata_pelajaran ?? '-' }}</span>
                                        @if ($item->mataPelajaran?->singkatan)
                                            <span class="badge badge-light ml-1">{{ $item->mataPelajaran->singkatan }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">Kelas {{ $item->tingkatan_kelas }}</span>
                                    </td>
                                    <td class="text-center font-weight-bold" style="color: #c2410c;">
                                        <div>{{ rtrim(rtrim(number_format((float) $item->nilai_kkm, 2, '.', ''), '0'), '.') }}</div>
                                        <div style="font-size: 0.68rem; font-weight: 600; color: {{ ($item->bobot_ujian ?? 0) > 0 ? '#4338ca' : '#64748b' }};">
                                            {{ ($item->bobot_ujian ?? 0) > 0 ? "{$item->bobot_tp}% TP • {$item->bobot_ujian}% Ujian" : '100% TP' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="kkm-pred-list">
                                            @foreach ($item->predikats as $pred)
                                                @php
                                                    $minLabel = rtrim(rtrim(number_format((float) $pred->nilai_min, 2, '.', ''), '0'), '.');
                                                    $maxLabel = rtrim(rtrim(number_format((float) $pred->nilai_max, 2, '.', ''), '0'), '.');
                                                @endphp
                                                <span class="kkm-pred-chip {{ $pred->is_tuntas ? '' : 'is-fail' }}"
                                                    title="{{ $pred->keterangan }}">
                                                    <strong>{{ $pred->predikat }}</strong>: {{ $minLabel }}–{{ $maxLabel }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="{{ route('penilaian-kkm.edit', $item->id) }}"
                                                class="btn btn-sm btn-warning text-white" title="Ubah KKM & Rentang">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('penilaian-kkm.destroy', $item->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger delete-button" title="Hapus KKM">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

