@php
    $kkmValue = (int) ($ekstrakurikuler->kkm ?: \App\Models\Admin\DataMaster\Ekstrakurikuler::DEFAULT_KKM);
    $materiValue = trim((string) ($ekstrakurikuler->materi ?? ''));
    $fromAdminShow = $fromAdminShow ?? false;
    $isWajibMode = $isWajibMode ?? false;
    $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
    $finalisasi = $finalisasi ?? null;
    $isLocked = (bool) ($finalisasi['is_final'] ?? false);
@endphp

<form action="{{ $formAction }}" method="POST" id="ekskulGradingForm">
    @csrf
    <input type="hidden" name="periode" value="{{ $periode }}">
    @if ($fromAdminShow)
        <input type="hidden" name="from_admin_show" value="1">
    @endif

    <!-- TABEL INPUT NILAI ANGKA ANGGOTA EKSTRAKURIKULER -->
    <div class="wk-surface">
        <div class="wk-surface-head">
            <div>
                <h6 class="wk-surface-title">
                    <i class="fa fa-clipboard-check text-primary mr-1"></i>
                    @if ($isWajibMode && isset($selectedKelas) && $selectedKelas)
                        Input Nilai Pramuka (Wajib) — Kelas {{ $selectedKelas->nama_kelas }}
                    @else
                        Input Nilai Peserta {{ $ekstrakurikuler->nama_ekstrakurikuler }}
                    @endif
                </h6>
                <div class="small text-muted">
                    Total <strong>{{ $members->count() }}</strong> siswa &bull; Rapor hanya menampilkan <strong>Predikat (SAB, BSH, SB, MB)</strong>
                    @if ($materiValue !== '')
                        &bull; Materi: <em>{{ \Illuminate\Support\Str::limit($materiValue, 85) }}</em>
                    @endif
                </div>
            </div>

            @if ($members->isNotEmpty())
                <div class="wk-search-wrap">
                    <i class="fa fa-search"></i>
                    <input type="text" id="ekskulMemberSearch" class="wk-search-input"
                        placeholder="Cari nama, NIS, atau kelas...">
                </div>
            @endif
        </div>

        @if ($members->isEmpty())
            <div class="p-5 text-center text-muted small">
                <div class="mx-auto mb-2 d-flex align-items-center justify-content-center"
                    style="width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; color: #94a3b8; font-size: 1.2rem;">
                    <i class="fa fa-user-slash"></i>
                </div>
                <div class="font-weight-bold text-dark mb-1">Belum Ada Peserta Terdaftar</div>
                Belum ada siswa yang terdaftar pada ekstrakurikuler <strong>{{ $ekstrakurikuler->nama_ekstrakurikuler }}</strong>.
            </div>
        @else
            <div class="table-responsive">
                <table class="wk-table" id="ekskulGradingTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 52px;">No</th>
                            <th style="min-width: 220px;">Siswa</th>
                            <th style="width: 140px;">Kelas</th>
                            <th class="text-center" style="width: 120px;">Nilai (0–100)</th>
                            <th class="text-center" style="width: 185px;">Predikat Rapor</th>
                            <th style="min-width: 300px;">Deskripsi Capaian Rapor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $idx => $member)
                            @php
                                $ak = $member->anggotaKelas;
                                $siswa = $ak?->siswa;
                                $kelas = $ak?->kelas;
                                $inputKey = $isWajibMode && $ak?->id ? $ak->id : $member->id;
                                $siswaName = $siswa?->nama_lengkap ?? ($ak?->siswa_nis ?? '-');
                                $currentNilai = old("nilai.{$inputKey}", $member->nilai);
                                $currentPredikat = $member->predikat;
                                $displayDeskripsi = $currentPredikat
                                    ? (string) \App\Models\Admin\DataMaster\Ekstrakurikuler::buildAutoDeskripsi(
                                        $currentPredikat,
                                        $materiValue,
                                        $ekstrakurikuler->nama_ekstrakurikuler
                                    )
                                    : trim((string) $member->keterangan);
                                $predikatPillStyle = match (strtoupper((string) $currentPredikat)) {
                                    'SAB' => 'background: #ecfdf5; color: #047857;',
                                    'BSH' => 'background: #eff6ff; color: #1d4ed8;',
                                    'SB' => 'background: #ecfeff; color: #0e7490;',
                                    'MB' => 'background: #fffbeb; color: #b45309;',
                                    default => 'background: #f1f5f9; color: #64748b;',
                                };
                                $searchIndex = mb_strtolower(implode(' ', array_filter([
                                    $siswaName,
                                    $ak?->siswa_nis,
                                    $kelas?->nama_kelas,
                                    $currentPredikat,
                                ])));
                            @endphp
                            <tr data-member-row="{{ $inputKey }}" data-search-text="{{ $searchIndex }}">
                                <td class="text-center text-muted font-weight-semibold">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center" style="gap: 0.7rem;">
                                        @if ($siswa?->avatar_url)
                                            <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb">
                                        @else
                                            <div class="wk-avatar-fallback">
                                                {{ $siswa?->inisial_nama ?? mb_strtoupper(mb_substr($siswaName, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div style="min-width: 0;">
                                            <div class="font-weight-bold text-dark" style="font-size: 0.84rem; line-height: 1.25;">
                                                {{ $siswaName }}
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.71rem;">
                                                NIS: <span class="font-weight-semibold">{{ $ak?->siswa_nis ?? '-' }}</span>
                                                @if ($siswa?->jenis_kelamin_label)
                                                    &bull; {{ $siswa->jenis_kelamin_label }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($kelas)
                                        <span class="badge badge-light border px-2 py-1 font-weight-semibold text-dark" style="font-size: 0.73rem;">
                                            {{ $kelas->nama_kelas }}
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 0.69rem;">
                                            {{ strtoupper((string) $kelas->jenjang_kelas) }} &bull; Tingkat {{ $kelas->tingkatan_kelas }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="number"
                                        name="nilai[{{ $inputKey }}]"
                                        min="0" max="100" step="1"
                                        class="ekskul-nilai-input"
                                        data-member-id="{{ $inputKey }}"
                                        placeholder="-"
                                        @disabled($isLocked)
                                        value="{{ $currentNilai }}">
                                </td>
                                <td class="text-center">
                                    <span class="wk-badge-status ekskul-predikat-badge"
                                        data-predikat-for="{{ $inputKey }}"
                                        style="{{ $predikatPillStyle }} font-size: 0.76rem; padding: 0.25rem 0.7rem;">
                                        {{ $currentPredikat ?: '-' }}
                                    </span>
                                    <div class="small text-muted mt-1 ekskul-predikat-label"
                                        data-predikat-label-for="{{ $inputKey }}"
                                        style="font-size: 0.69rem;">
                                        {{ $member->predikat_label ?? 'Belum dinilai' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="small {{ $displayDeskripsi !== '' ? 'text-dark' : 'text-muted font-italic' }}"
                                        data-keterangan-text-for="{{ $inputKey }}"
                                        style="font-size: 0.79rem; line-height: 1.45;">
                                        {{ $displayDeskripsi !== '' ? $displayDeskripsi : 'Deskripsi otomatis tampil setelah nilai diisi.' }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ekskul-save-bar">
                <div class="small text-muted">
                    <i class="fa fa-info-circle text-warning mr-1"></i>
                    Predikat (<strong>SAB, BSH, SB, MB</strong>) dan deskripsi rapor dihitung otomatis saat nilai disimpan.
                </div>

                @if ($isLocked)
                    <span class="badge badge-success px-3 py-2" style="border-radius: 10px; font-size: 0.78rem;">
                        <i class="fa fa-lock mr-1"></i> Terkunci (Final)
                    </span>
                @else
                    <button type="submit" class="btn text-white font-weight-bold px-4 shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                        <i class="fa fa-save mr-1"></i> Simpan Nilai
                    </button>
                @endif
            </div>
        @endif
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            const configuredKkm = @json($kkmValue);
            const configuredMateri = @json($materiValue);
            const searchInput = document.getElementById('ekskulMemberSearch');
            const ekskulName = @json($ekstrakurikuler->nama_ekstrakurikuler);
            const nilaiInputs = document.querySelectorAll('.ekskul-nilai-input');

            const PREDIKAT_META = {
                SAB: { label: 'Sangat Baik', bg: '#ecfdf5', color: '#047857' },
                BSH: { label: 'Berkembang Sesuai Harapan', bg: '#eff6ff', color: '#1d4ed8' },
                SB: { label: 'Sedang Berkembang', bg: '#ecfeff', color: '#0e7490' },
                MB: { label: 'Mulai Berkembang', bg: '#fffbeb', color: '#b45309' }
            };

            function computeRanges(kkmRaw) {
                const kkm = Math.max(1, Math.min(100, Math.round(Number(kkmRaw) || 75)));
                if (kkm >= 98) {
                    return [
                        { code: 'SAB', min: kkm, max: 100 },
                        { code: 'MB', min: 0, max: kkm - 1 }
                    ];
                }
                const totalPassing = 100 - kkm + 1;
                const baseSpan = Math.floor(totalPassing / 3);
                const rem = totalPassing % 3;
                const spanA = baseSpan + (rem >= 1 ? 1 : 0);
                const spanB = baseSpan + (rem >= 2 ? 1 : 0);

                const minA = 100 - spanA + 1;
                const maxB = minA - 1;
                const minB = maxB - spanB + 1;
                const maxC = minB - 1;

                return [
                    { code: 'SAB', min: minA, max: 100 },
                    { code: 'BSH', min: minB, max: maxB },
                    { code: 'SB', min: kkm, max: maxC },
                    { code: 'MB', min: 0, max: kkm - 1 }
                ];
            }

            const activeRanges = computeRanges(configuredKkm);

            function resolveCode(nilaiRaw) {
                if (nilaiRaw === '' || nilaiRaw === null || isNaN(Number(nilaiRaw))) {
                    return null;
                }
                const val = Math.max(0, Math.min(100, Math.round(Number(nilaiRaw))));
                for (const r of activeRanges) {
                    if (val >= r.min && val <= r.max) {
                        return r.code;
                    }
                }
                return null;
            }

            function buildAutoDeskripsi(code) {
                if (!code || !PREDIKAT_META[code]) {
                    return '';
                }
                const cleanMateri = String(configuredMateri || '').trim().replace(/\.+$/, '');
                const target = cleanMateri !== ''
                    ? cleanMateri
                    : ('kegiatan ekstrakurikuler ' + ekskulName);

                switch (code) {
                    case 'SAB':
                        return 'Mengikuti kegiatan dengan sangat baik serta mahir dalam ' + target + '.';
                    case 'BSH':
                        return 'Mengikuti kegiatan dengan baik dan berkembang sesuai harapan dalam ' + target + '.';
                    case 'SB':
                        return 'Sedang berkembang dan cukup baik dalam mengikuti ' + target + '.';
                    case 'MB':
                        return 'Mulai berkembang dan perlu bimbingan lebih lanjut dalam ' + target + '.';
                    default:
                        return '';
                }
            }

            nilaiInputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    const memberId = input.getAttribute('data-member-id');
                    const code = resolveCode(input.value);
                    const badgeEl = document.querySelector('[data-predikat-for="' + memberId + '"]');
                    const labelEl = document.querySelector('[data-predikat-label-for="' + memberId + '"]');
                    const ketTextEl = document.querySelector('[data-keterangan-text-for="' + memberId + '"]');

                    if (badgeEl) {
                        const meta = code ? PREDIKAT_META[code] : null;
                        badgeEl.style.background = meta ? meta.bg : '#f1f5f9';
                        badgeEl.style.color = meta ? meta.color : '#64748b';
                        badgeEl.textContent = code || '-';
                    }
                    if (labelEl) {
                        labelEl.textContent = code ? PREDIKAT_META[code].label : 'Belum dinilai';
                    }
                    if (ketTextEl) {
                        const deskripsi = code ? buildAutoDeskripsi(code) : '';
                        if (deskripsi !== '') {
                            ketTextEl.textContent = deskripsi;
                            ketTextEl.className = 'small text-dark';
                        } else {
                            ketTextEl.textContent = 'Deskripsi otomatis tampil setelah nilai diisi.';
                            ketTextEl.className = 'small text-muted font-italic';
                        }
                    }
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = searchInput.value.trim().toLowerCase();
                    document.querySelectorAll('#ekskulGradingTable tbody tr[data-member-row]').forEach(function (row) {
                        const text = row.getAttribute('data-search-text') || '';
                        row.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endpush
