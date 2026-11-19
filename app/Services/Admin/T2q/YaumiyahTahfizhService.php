<?php

namespace App\Services\Admin\T2q;

use App\Models\Admin\T2q\YaumiyahTahfizh;

class YaumiyahTahfizhService extends BaseYaumiyahT2qService
{
    protected function modelClass(): string
    {
        return YaumiyahTahfizh::class;
    }

    public function modeKey(): string
    {
        return 'tahfizh';
    }

    public function usesDaftarJilid(): bool
    {
        return false;
    }

    /**
     * Menyusun payload baris Yaumiyah Tahfizh (fokus hafalan surah & ayat).
     */
    protected function buildRowPayload(array $rowInput, array $lookupMaps): ?array
    {
        $rawNilai = trim((string) ($rowInput['nilai'] ?? ''));
        $surahId = (int) ($rowInput['akademik_master_t2q_surah_id'] ?? 0);

        if ($rawNilai === '' || $surahId <= 0 || ! is_numeric($rawNilai)) {
            return null;
        }

        $rawAngkaId = (int) ($rowInput['akademik_master_t2q_angka_arab_id'] ?? 0);

        return [
            'akademik_master_t2q_surah_id' => $surahId,
            'akademik_master_t2q_angka_arab_id' => $rawAngkaId > 0 ? $rawAngkaId : null,
            'nilai' => round((float) $rawNilai, 2),
        ];
    }
}
