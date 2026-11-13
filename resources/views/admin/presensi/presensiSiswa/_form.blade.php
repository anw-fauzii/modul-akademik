@php
    $batasJamMasuk = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;
    $selectedAnggotaId = old('anggota_kelas_id', $presensiSiswa->anggota_kelas_id ?? '');
    $selectedTanggal = old('tanggal', isset($presensiSiswa) && $presensiSiswa->tanggal ? $presensiSiswa->tanggal->format('Y-m-d') : now()->format('Y-m-d'));
    $selectedJamMasuk = old('jam_masuk', isset($presensiSiswa) && $presensiSiswa->jam_masuk ? substr((string) $presensiSiswa->jam_masuk, 0, 5) : now()->format('H:i'));

    $defaultLateMinutes = ! isset($presensiSiswa)
        ? \App\Models\Admin\Presensi\PresensiSiswa::calculateKeterlambatanMenit(is_string($selectedJamMasuk) ? $selectedJamMasuk : null)
        : 0;
    $defaultStatus = isset($presensiSiswa)
        ? $presensiSiswa->status
        : ($defaultLateMinutes > 0 ? 'terlambat' : 'hadir');

    $selectedStatus = old('status', $defaultStatus);
    $selectedKeterlambatan = old('keterlambatan_menit', isset($presensiSiswa) ? $presensiSiswa->keterlambatan_menit : $defaultLateMinutes);

    $statusThemes = [
        'hadir' => ['code' => 'H', 'desc' => 'Tepat waktu', 'ring' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700'],
        'terlambat' => ['code' => 'T', 'desc' => 'Masuk telat', 'ring' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700'],
        'izin' => ['code' => 'I', 'desc' => 'Izin resmi', 'ring' => 'peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700'],
        'sakit' => ['code' => 'S', 'desc' => 'Keterangan sakit', 'ring' => 'peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-700'],
        'alpa' => ['code' => 'A', 'desc' => 'Tanpa keterangan', 'ring' => 'peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700'],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label for="anggota_kelas_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
            Siswa (Anggota Kelas) <span class="text-rose-500">*</span>
        </label>
        <select name="anggota_kelas_id" id="anggota_kelas_id" required
            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
            <option value="">-- Pilih Siswa Anggota Kelas --</option>
            @foreach ($anggotaKelasOptions as $anggota)
                <option value="{{ $anggota->id }}" @selected((string) $selectedAnggotaId === (string) $anggota->id)>
                    {{ $anggota->siswa?->nama_lengkap ?? '-' }} ({{ $anggota->siswa?->nis ?? '-' }})
                    — {{ $anggota->kelas ? $anggota->kelas->jenjang_kelas.' '.$anggota->kelas->nama_kelas : '-' }}
                    @if ($anggota->kelas?->tahunAjaran)
                        [{{ $anggota->kelas->tahunAjaran->nama_tahun_ajaran }} - {{ $anggota->kelas->tahunAjaran->semester }}]
                    @endif
                </option>
            @endforeach
        </select>
        @error('anggota_kelas_id')
            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
            Status Kehadiran <span class="text-rose-500">*</span>
        </label>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
            @foreach ($statusOptions as $value => $label)
                @php
                    $theme = $statusThemes[$value] ?? ['code' => '?', 'desc' => '', 'ring' => 'peer-checked:border-orange-500 peer-checked:bg-orange-50'];
                @endphp
                <label class="cursor-pointer block">
                    <input type="radio" name="status" value="{{ $value }}" class="peer sr-only js-status-radio"
                        @checked($selectedStatus === $value) required>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 transition-all hover:border-slate-300 {{ $theme['ring'] }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold">{{ $label }}</span>
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-white/80 text-xs font-extrabold shadow-xs">
                                {{ $theme['code'] }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-[11px] opacity-75">{{ $theme['desc'] }}</p>
                    </div>
                </label>
            @endforeach
        </div>
        @error('status')
            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tanggal" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
            Tanggal Presensi <span class="text-rose-500">*</span>
        </label>
        <input type="date" name="tanggal" id="tanggal" value="{{ $selectedTanggal }}" required
            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
        @error('tanggal')
            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="jam_masuk" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                Jam Masuk
            </label>
            <input type="time" name="jam_masuk" id="jam_masuk" value="{{ $selectedJamMasuk }}"
                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
            <p class="mt-1 text-[11px] text-slate-500">
                Maksimal tepat waktu pukul <strong>{{ $batasJamMasuk }}</strong>.
            </p>
            @error('jam_masuk')
                <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="keterlambatan_menit" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                Terlambat (Menit)
            </label>
            <input type="number" name="keterlambatan_menit" id="keterlambatan_menit" min="0" max="1440"
                value="{{ $selectedKeterlambatan }}"
                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
            <p class="mt-1 text-[11px] text-slate-500">
                Terisi otomatis jika jam masuk melewati {{ $batasJamMasuk }}.
            </p>
            @error('keterlambatan_menit')
                <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

@push('js')
    <script>
        (function () {
            const BATAS_JAM_MASUK = @json($batasJamMasuk);
            const radios = document.querySelectorAll('.js-status-radio');
            const jamMasukInput = document.getElementById('jam_masuk');
            const keterlambatanInput = document.getElementById('keterlambatan_menit');

            if (!radios.length || !jamMasukInput || !keterlambatanInput) {
                return;
            }

            function calculateLateMinutes(jamValue) {
                if (!jamValue || !/^\d{2}:\d{2}/.test(jamValue)) {
                    return 0;
                }

                const [maxH, maxM] = BATAS_JAM_MASUK.split(':').map(Number);
                const [h, m] = jamValue.slice(0, 5).split(':').map(Number);

                return Math.max(0, (h * 60 + m) - (maxH * 60 + maxM));
            }

            function getSelectedStatus() {
                const checked = document.querySelector('.js-status-radio:checked');
                return checked ? checked.value : 'hadir';
            }

            function setStatus(statusValue) {
                const target = document.querySelector('.js-status-radio[value="' + statusValue + '"]');
                if (target) {
                    target.checked = true;
                }
            }

            function applyAutoLateFromJam() {
                const status = getSelectedStatus();
                if (status !== 'hadir' && status !== 'terlambat') {
                    return;
                }

                const lateMinutes = calculateLateMinutes(jamMasukInput.value);
                if (lateMinutes > 0) {
                    setStatus('terlambat');
                    keterlambatanInput.readOnly = false;
                    keterlambatanInput.value = String(lateMinutes);
                } else if (jamMasukInput.value) {
                    setStatus('hadir');
                    keterlambatanInput.readOnly = true;
                    keterlambatanInput.value = '0';
                }
            }

            function syncFieldsByStatus() {
                const status = getSelectedStatus();
                const isPresent = status === 'hadir' || status === 'terlambat';

                jamMasukInput.disabled = !isPresent;
                if (!isPresent) {
                    jamMasukInput.value = '';
                }

                keterlambatanInput.readOnly = status !== 'terlambat';
                if (status !== 'terlambat') {
                    keterlambatanInput.value = '0';
                } else if (Number(keterlambatanInput.value || 0) === 0) {
                    const lateMinutes = calculateLateMinutes(jamMasukInput.value);
                    if (lateMinutes > 0) {
                        keterlambatanInput.value = String(lateMinutes);
                    }
                }
            }

            jamMasukInput.addEventListener('input', applyAutoLateFromJam);
            jamMasukInput.addEventListener('change', applyAutoLateFromJam);
            radios.forEach((radio) => radio.addEventListener('change', syncFieldsByStatus));
            syncFieldsByStatus();
        })();
    </script>
@endpush
