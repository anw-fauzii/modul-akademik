@php
    $kopYayasan = $sekolah?->nama_yayasan ?: 'Yayasan Prima Insani';
    $kopSekolah = $sekolah?->nama_sekolah ?: (strtoupper((string) $kelas->jenjang_kelas).' Prima Insani');
    $kopAlamat = $sekolah?->alamat_lengkap ?: '-';
    $kopKontak = collect([
        $sekolah?->telepon ? 'Telp. '.$sekolah->telepon : null,
        $sekolah?->email ?: null,
        $sekolah?->website ?: null,
    ])->filter()->implode(' | ');
@endphp

<table class="kop-table">
    <tr>
        @if ($logoDataUri)
            <td style="width: 72px; text-align: left;">
                <img src="{{ $logoDataUri }}" alt="Logo" class="kop-logo">
            </td>
        @endif
        <td class="text-center">
            <div class="kop-yayasan uppercase">{{ $kopYayasan }}</div>
            <div class="kop-sekolah uppercase">{{ $kopSekolah }}</div>
            <div class="kop-alamat">
                {{ $kopAlamat }}
                @if ($kopKontak !== '')
                    <br>{{ $kopKontak }}
                @endif
            </div>
        </td>
        @if ($logoDataUri)
            <td style="width: 72px;"></td>
        @endif
    </tr>
</table>
<div class="kop-line-top"></div>
<div class="kop-line-bottom"></div>
