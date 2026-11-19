<?php

namespace App\Services\Admin\T2q;

use App\Models\Admin\T2q\YaumiyahTahsin;

class YaumiyahTahsinService extends BaseYaumiyahT2qService
{
    protected function modelClass(): string
    {
        return YaumiyahTahsin::class;
    }

    public function modeKey(): string
    {
        return 'tahsin';
    }

    public function usesDaftarJilid(): bool
    {
        return true;
    }

    /**
     * Menyusun payload baris Yaumiyah Tahsin; surah otomatis dikosongkan bila jilid bertipe halaman.
     */
    protected function buildRowPayload(array $rowInput, array $lookupMaps): ?array
    {
        $rawNilai = trim((string) ($rowInput['nilai'] ?? ''));
        $jilidId = (int) ($rowInput['akademik_master_t2q_daftar_jilid_id'] ?? 0);

        if ($rawNilai === '' || $jilidId <= 0 || ! is_numeric($rawNilai)) {
            return null;
        }

        $jenis = strtolower(trim((string) ($lookupMaps['jilidJenisMap'][$jilidId] ?? '')));
        $rawSurahId = (int) ($rowInput['akademik_master_t2q_surah_id'] ?? 0);
        $rawAngkaId = (int) ($rowInput['akademik_master_t2q_angka_arab_id'] ?? 0);

        return [
            'akademik_master_t2q_daftar_jilid_id' => $jilidId,
            'akademik_master_t2q_surah_id' => ($jenis === 'quran' && $rawSurahId > 0) ? $rawSurahId : null,
            'akademik_master_t2q_angka_arab_id' => $rawAngkaId > 0 ? $rawAngkaId : null,
            'nilai' => round((float) $rawNilai, 2),
        ];
    }
}

