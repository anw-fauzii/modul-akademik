<?php

namespace Database\Seeders;

use App\Models\Admin\DataMaster\KomentarT2q;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomentarT2qSeeder extends Seeder
{
    /**
     * Mengisi tabel master komentar T2Q (kelebihan & kekurangan) tanpa duplikasi.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $rows = [];
        $seen = [];

        foreach (KomentarT2q::DEFAULT_COMMENTS as $raw) {
            $komentar = trim(preg_replace('/\s+/', ' ', $raw) ?? '');
            if ($komentar === '') {
                continue;
            }

            $key = mb_strtolower($komentar);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'komentar' => $komentar,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('akademik_master_t2q_komentar')->upsert(
                $rows,
                ['komentar'],
                ['updated_at']
            );
        }
    }
}
