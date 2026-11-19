@php
    $switcherRoute = $switcherRoute ?? (request()->routeIs('wali-kelas.dashboard') ? 'wali-kelas.dashboard' : 'home');
@endphp

<div class="wk-class-switcher">
    <span class="small font-weight-bold text-muted mr-1">Kelas Binaan Aktif:</span>
    @foreach ($assignedClasses as $kelasOption)
        @php
            $isCurrentClass = (int) $kelasOption->id === (int) $selectedKelas->id;
            $optionRole = trim((string) $kelasOption->karyawan_nipy) === $nipy ? 'Wali Kelas' : 'Pendamping';
        @endphp
        <a href="{{ route($switcherRoute, ['kelas_id' => $kelasOption->sqid]) }}"
            class="wk-class-pill {{ $isCurrentClass ? 'is-active' : '' }}">
            <span>{{ $kelasOption->nama_kelas }}</span>
            <span style="opacity: 0.8; font-weight: 600;">({{ $optionRole }})</span>
        </a>
    @endforeach
</div>

