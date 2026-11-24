@extends('layouts.appui')

@section('title')
    <title>Detail Prestasi Siswa - {{ $prestasiSiswa->nama_prestasi }}</title>
@endsection

@pushOnce('css')
    <style>
        .prestasi-detail-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 58%, #334155 100%);
            border-radius: 16px;
            padding: 1.4rem 1.65rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 26px -6px rgba(15, 23, 42, 0.25);
        }

        .prestasi-detail-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.2rem 1.35rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .prestasi-detail-kicker {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ea580c;
            border-bottom: 1.5px solid #fed7aa;
            padding-bottom: 0.5rem;
            margin-bottom: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .prestasi-member-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.65rem;
        }

        .prestasi-member-tile {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0.75rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            text-decoration: none !important;
            transition: border-color 0.14s ease, background-color 0.14s ease;
        }

        .prestasi-member-tile:hover {
            border-color: #fdba74;
            background: #fff7ed;
        }

        .prestasi-photo-full {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 0.5rem;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $siswaCollection = $prestasiSiswa->relationLoaded('siswa') ? $prestasiSiswa->siswa : collect();
        $tahunAjaran = $prestasiSiswa->relationLoaded('tahunAjaran') ? $prestasiSiswa->tahunAjaran : null;
        $fotoUrl = $prestasiSiswa->foto_url;
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="fa fa-trophy" style="color: white;"></i>
                    </div>
                    <div>
                        Detail Prestasi Siswa
                        <div class="page-title-subheading">
                            Rincian pencapaian siswa (individu/beregu) beserta catatan dan dokumentasi foto.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('prestasi-siswa.edit', $prestasiSiswa) }}" class="btn btn-primary shadow-sm" style="border-radius: 8px;">
                        <i class="fa fa-edit mr-1"></i> Edit Prestasi
                    </a>
                    <a href="{{ route('prestasi-siswa.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- HERO RINGKASAN PRESTASI -->
        <div class="prestasi-detail-hero mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 1rem;">
                <div>
                    <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 0.45rem;">
                        <span class="badge {{ $prestasiSiswa->kategori_badge_class }} px-2 py-1 font-weight-bold">
                            {{ $prestasiSiswa->kategori_label }}
                        </span>
                        <span class="badge {{ $prestasiSiswa->tingkat_badge_class }} px-2 py-1 font-weight-bold">
                            Tingkat {{ $prestasiSiswa->tingkat }}
                        </span>
                        <span class="badge badge-warning text-dark px-2 py-1 font-weight-bold">
                            <i class="fa fa-award mr-1"></i> {{ $prestasiSiswa->peringkat }}
                        </span>
                        <span class="badge badge-light text-dark px-2 py-1 font-weight-bold">
                            <i class="fa {{ $prestasiSiswa->is_beregu ? 'fa-users' : 'fa-user' }} mr-1 text-warning"></i>
                            {{ $prestasiSiswa->jenis_peserta_label }}
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-white mb-1" style="font-size: 1.35rem;">
                        {{ $prestasiSiswa->nama_prestasi }}
                    </h3>

                    <div class="small text-slate-200">
                        <i class="fa fa-building mr-1 text-warning"></i> Penyelenggara: <strong>{{ $prestasiSiswa->penyelenggara }}</strong>
                        &bull;
                        <i class="fa fa-calendar-alt ml-1 mr-1 text-info"></i> Tanggal: <strong>{{ $prestasiSiswa->tanggal_formatted }}</strong>
                        @if ($tahunAjaran)
                            &bull; TA {{ $tahunAjaran->nama_tahun_ajaran }} (Semester {{ strtoupper($tahunAjaran->semester) }})
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-3">
                <div class="prestasi-detail-card">
                    <div class="prestasi-detail-kicker">
                        <i class="fa fa-users"></i> Penerima Prestasi ({{ $siswaCollection->count() }} Siswa)
                    </div>

                    <div class="prestasi-member-grid mb-4">
                        @forelse ($siswaCollection as $anggotaSiswa)
                            @php
                                $kelasAnggota = $anggotaSiswa->relationLoaded('kelas') ? $anggotaSiswa->kelas : null;
                            @endphp
                            <a href="{{ route('siswa.show', $anggotaSiswa) }}" class="prestasi-member-tile">
                                @if ($anggotaSiswa->avatar_url)
                                    <img src="{{ $anggotaSiswa->avatar_url }}" alt="{{ $anggotaSiswa->nama_lengkap }}"
                                        style="width: 38px; height: 38px; border-radius: 999px; object-fit: cover; flex-shrink: 0;">
                                @else
                                    <div class="d-inline-flex align-items-center justify-content-center font-weight-bold"
                                        style="width: 38px; height: 38px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.85rem; flex-shrink: 0;">
                                        {{ $anggotaSiswa->inisial_nama }}
                                    </div>
                                @endif
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-slate-800 small text-truncate">
                                        {{ $anggotaSiswa->nama_lengkap }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.71rem;">
                                        NIS {{ $anggotaSiswa->nis }}
                                        @if ($kelasAnggota)
                                            &bull; {{ $kelasAnggota->nama_kelas }}
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-muted small">Belum ada siswa tertaut.</div>
                        @endforelse
                    </div>

                    <div class="prestasi-detail-kicker">
                        <i class="fa fa-info-circle"></i> Informasi Lengkap &amp; Keterangan
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted font-weight-600">Kategori</div>
                            <div class="font-weight-bold text-slate-800">{{ $prestasiSiswa->kategori_label }}</div>
                        </div>

                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted font-weight-600">Tingkat &amp; Peringkat</div>
                            <div class="font-weight-bold text-slate-800">
                                {{ $prestasiSiswa->peringkat }} &bull; Tingkat {{ $prestasiSiswa->tingkat }}
                            </div>
                        </div>

                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted font-weight-600">Penyelenggara</div>
                            <div class="font-weight-bold text-slate-800">{{ $prestasiSiswa->penyelenggara }}</div>
                        </div>

                        <div class="col-sm-6 mb-2">
                            <div class="small text-muted font-weight-600">Tanggal Perolehan</div>
                            <div class="font-weight-bold text-slate-800">{{ $prestasiSiswa->tanggal_formatted }}</div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="small text-muted font-weight-bold mb-1">
                            <i class="fa fa-sticky-note text-warning mr-1"></i> Keterangan Tambahan:
                        </div>
                        @if (filled($prestasiSiswa->keterangan))
                            <div class="p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0; white-space: pre-line; font-size: 0.88rem; color: #1e293b;">{{ $prestasiSiswa->keterangan }}</div>
                        @else
                            <div class="text-muted small italic">Tidak ada keterangan tambahan.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-3">
                <div class="prestasi-detail-card">
                    <div class="prestasi-detail-kicker">
                        <i class="fa fa-camera"></i> Dokumentasi Foto / Piagam
                    </div>

                    @if ($fotoUrl)
                        <div class="text-center">
                            <img src="{{ $fotoUrl }}" alt="Foto {{ $prestasiSiswa->nama_prestasi }}" class="prestasi-photo-full mb-2">
                            <div>
                                <a href="{{ $fotoUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 8px;">
                                    <i class="fa fa-external-link-alt mr-1"></i> Buka Ukuran Penuh
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5 px-3 rounded" style="background: #f8fafc; border: 1.5px dashed #cbd5e1;">
                            <i class="fa fa-image text-muted mb-2" style="font-size: 2rem;"></i>
                            <div class="font-weight-bold text-slate-700 small">Belum Ada Foto Dokumentasi</div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                Anda dapat mengunggah foto piagam atau penyerahan piala melalui menu Edit Prestasi.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
