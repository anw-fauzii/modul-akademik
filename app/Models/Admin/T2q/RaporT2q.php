<?php

namespace App\Models\Admin\T2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\KomentarT2q;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Concerns\HasSqidRouteKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'anggota_t2q_id',
    'periode',
    'tahsin_yaumiyah_id',
    'tahsin_materi',
    'tahsin_nilai',
    'tahsin_predikat',
    'tahsin_keunggulan',
    'tahsin_peningkatan',
    'tahsin_deskripsi',
    'tahfizh_yaumiyah_id',
    'tahfizh_materi',
    'tahfizh_nilai',
    'tahfizh_predikat',
    'tahfizh_keunggulan',
    'tahfizh_peningkatan',
    'tahfizh_deskripsi',
    'updated_by_user_id',
])]
#[Table('akademik_t2q_rekap_rapor')]
class RaporT2q extends Model
{
    use HasSqidRouteKey;

    public const int DEFAULT_KKM = Ekstrakurikuler::DEFAULT_KKM;

    public const string MODE_TAHSIN = 'tahsin';

    public const string MODE_TAHFIZH = 'tahfizh';

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
            'anggota_t2q_id' => 'integer',
            'tahsin_yaumiyah_id' => 'integer',
            'tahsin_nilai' => 'float',
            'tahfizh_yaumiyah_id' => 'integer',
            'tahfizh_nilai' => 'float',
            'updated_by_user_id' => 'integer',
        ];
    }

    /**
     * Rentang predikat rapor T2Q menggunakan skala Ekstrakurikuler minimal 75 (MB, SB, BSH, SAB).
     *
     * @return list<array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: int,
     *     nilai_max: int,
     *     is_tuntas: bool,
     *     urutan: int
     * }>
     */
    public static function predikatRanges(): array
    {
        return Ekstrakurikuler::generatePredikatRanges(self::DEFAULT_KKM);
    }

    /**
     * Menentukan predikat rapor T2Q dari nilai angka menggunakan skala minimal 75 (MB → SAB).
     *
     * @return array{
     *     predikat: string,
     *     keterangan: string,
     *     nilai_min: int,
     *     nilai_max: int,
     *     is_tuntas: bool,
     *     urutan: int
     * }|null
     */
    public static function resolvePredikatMeta(int|float|null $nilai): ?array
    {
        return Ekstrakurikuler::resolvePredikatForNilai($nilai, self::DEFAULT_KKM);
    }

    /**
     * Klausa pembuka deskripsi rapor T2Q per kode predikat.
     */
    public static function openingClauseForPredikat(?string $predikatCode): ?string
    {
        return match (strtoupper(trim((string) $predikatCode))) {
            'SAB' => 'Ananda menunjukkan capaian sangat baik',
            'BSH' => 'Ananda berkembang sesuai harapan',
            'SB' => 'Ananda sedang berkembang',
            'MB' => 'Ananda mulai berkembang',
            default => null,
        };
    }

    /**
     * Menormalkan penulisan nama surah agar selalu diawali "Surah " tanpa duplikasi.
     */
    public static function formatSurahLabel(?Surah $surah): string
    {
        $rawName = trim((string) ($surah?->nama_surah ?? ''));
        if ($rawName === '') {
            return '';
        }

        if (preg_match('/^(surah|surat)\s+/i', $rawName) === 1) {
            return 'Surah '.trim((string) preg_replace('/^(surah|surat)\s+/i', '', $rawName));
        }

        return 'Surah '.$rawName;
    }

    /**
     * Menyusun label materi Tahsin rapi untuk rapor (misal: "Jilid 4 halaman 11" atau "Tilawah Surah Al-Baqarah ayat 15").
     */
    public static function formatTahsinMateri(?DaftarJilid $jilid, ?Surah $surah, ?AngkaArab $angka): ?string
    {
        $jilidLabel = trim((string) ($jilid?->jilid_latin ?? ''));
        $surahLabel = self::formatSurahLabel($surah);
        $angkaText = trim((string) ($angka?->angka_latin ?? ''));

        if ($jilidLabel === '' && $surahLabel === '') {
            return null;
        }

        $isQuran = $surahLabel !== ''
            || strtolower(trim((string) ($jilid?->jenis ?? ''))) === 'quran';

        if ($surahLabel !== '') {
            $base = $jilidLabel !== '' ? "{$jilidLabel} {$surahLabel}" : $surahLabel;

            return $angkaText !== '' ? "{$base} ayat {$angkaText}" : $base;
        }

        if ($angkaText !== '') {
            $unit = $isQuran ? 'ayat' : 'halaman';

            return trim("{$jilidLabel} {$unit} {$angkaText}");
        }

        return $jilidLabel;
    }

    /**
     * Menyusun label materi Tahfidz rapi untuk rapor (misal: "Surah al-Buruj" atau "Surah al-Buruj ayat 15").
     *
     * @return array{label: ?string, has_ayat: bool, surah_label: ?string, ayat_label: ?string}
     */
    public static function formatTahfizhMateriDetail(?Surah $surah, ?AngkaArab $angka): array
    {
        $surahLabel = self::formatSurahLabel($surah);
        if ($surahLabel === '') {
            return [
                'label' => null,
                'has_ayat' => false,
                'surah_label' => null,
                'ayat_label' => null,
            ];
        }

        $ayatText = trim((string) ($angka?->angka_latin ?? ''));
        if ($ayatText !== '') {
            return [
                'label' => "{$surahLabel} ayat {$ayatText}",
                'has_ayat' => true,
                'surah_label' => $surahLabel,
                'ayat_label' => $ayatText,
            ];
        }

        return [
            'label' => $surahLabel,
            'has_ayat' => false,
            'surah_label' => $surahLabel,
            'ayat_label' => null,
        ];
    }

    /**
     * Memecah label materi Tahfidz (dari Yaumiyah maupun snapshot teks) untuk mendeteksi konteks ayat vs surah utuh.
     *
     * @return array{has_ayat: bool, surah_label: string, ayat_label: ?string}
     */
    public static function parseTahfizhMateriContext(string $materiLabel): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $materiLabel) ?? '');
        if ($clean === '') {
            return ['has_ayat' => false, 'surah_label' => '', 'ayat_label' => null];
        }

        if (preg_match('/^(.+?)\s+ayat\s+(.+)$/iu', $clean, $matches) === 1) {
            return [
                'has_ayat' => true,
                'surah_label' => trim($matches[1]),
                'ayat_label' => trim($matches[2]),
            ];
        }

        return [
            'has_ayat' => false,
            'surah_label' => $clean,
            'ayat_label' => null,
        ];
    }

    /**
     * Menyusun kalimat deskripsi otomatis untuk Rapor T2Q sesuai predikat skala 75, konteks materi/ayat, dan aspek pengamatan.
     */
    public static function buildAutoDeskripsi(
        string $mode,
        int|float|null $nilai,
        ?string $materiLabel,
        ?string $keunggulan = null,
        ?string $peningkatan = null
    ): ?string {
        if ($nilai === null) {
            return null;
        }

        $meta = self::resolvePredikatMeta($nilai);
        $code = $meta['predikat'] ?? null;
        $opening = self::openingClauseForPredikat($code);
        $cleanMateri = trim((string) $materiLabel);

        if ($opening === null || $cleanMateri === '') {
            return null;
        }

        $normMode = strtolower(trim($mode)) === self::MODE_TAHFIZH
            ? self::MODE_TAHFIZH
            : self::MODE_TAHSIN;

        if ($normMode === self::MODE_TAHFIZH) {
            $ctx = self::parseTahfizhMateriContext($cleanMateri);
            $core = $ctx['has_ayat'] && filled($ctx['ayat_label'])
                ? "{$opening} dalam menghafal dan melafalkan {$ctx['surah_label']} hingga ayat {$ctx['ayat_label']}"
                : "{$opening} dalam melafalkan {$ctx['surah_label']}";
        } else {
            $core = "{$opening} dalam menguasai kompetensi tahsin {$cleanMateri}";
        }

        $cleanKeunggulan = self::sanitizeFragment($keunggulan, '/^(terutama\s+(dalam\s+)?)/iu');
        $cleanPeningkatan = self::sanitizeFragment($peningkatan, '/^(perlu\s+peningkatan\s+(dalam\s+)?)/iu');

        $firstSentence = $cleanKeunggulan !== ''
            ? "{$core}, terutama dalam {$cleanKeunggulan}."
            : "{$core}.";

        if ($cleanPeningkatan !== '') {
            return "{$firstSentence} Perlu peningkatan dalam {$cleanPeningkatan}.";
        }

        if ($code === 'MB') {
            $defaultFocus = $normMode === self::MODE_TAHFIZH
                ? 'kelancaran hafalan'
                : 'kelancaran dan ketepatan bacaan';

            return "{$firstSentence} Perlu peningkatan dalam {$defaultFocus}.";
        }

        return $firstSentence;
    }

    private static function sanitizeFragment(?string $raw, string $redundantPrefixPattern): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', (string) ($raw ?? '')) ?? '');
        if ($clean === '') {
            return '';
        }

        $clean = trim((string) preg_replace($redundantPrefixPattern, '', $clean));

        return rtrim($clean, ". \t\n\r\0\x0B");
    }

    /**
     * Mengurai string aspek keunggulan/peningkatan tersimpan menjadi daftar item unik (mendukung item mengandung koma atau "dan").
     *
     * @param  list<string>  $knownOptions
     * @return list<string>
     */
    public static function parseAspectList(?string $raw, array $knownOptions = []): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', (string) ($raw ?? '')) ?? '');
        if ($clean === '') {
            return [];
        }

        $clean = rtrim($clean, ". \t\n\r\0\x0B");
        $catalog = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $v): string => trim((string) $v),
                [...$knownOptions, ...KomentarT2q::DEFAULT_COMMENTS]
            ),
            static fn (string $v): bool => $v !== ''
        )));

        usort($catalog, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $result = [];
        $seen = [];
        $remaining = $clean;
        $sepPattern = '/\A(?:\s*[;,]\s*(?:(?:dan|serta)\s+)?|\s+(?:dan|serta)\s+)/iu';

        $pushUnique = static function (string $item) use (&$result, &$seen): void {
            $trimmed = rtrim(trim($item), ". \t\n\r\0\x0B");
            if ($trimmed === '') {
                return;
            }
            $key = mb_strtolower($trimmed);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $trimmed;
            }
        };

        while ($remaining !== '') {
            $remaining = (string) preg_replace($sepPattern, '', $remaining);
            if ($remaining === '') {
                break;
            }

            $matchedOption = null;
            foreach ($catalog as $opt) {
                $optLen = mb_strlen($opt);
                if (mb_strlen($remaining) < $optLen) {
                    continue;
                }

                if (mb_strtolower(mb_substr($remaining, 0, $optLen)) === mb_strtolower($opt)) {
                    $after = mb_substr($remaining, $optLen);
                    if ($after === '' || preg_match($sepPattern, $after) === 1) {
                        $matchedOption = $opt;
                        $remaining = $after;
                        break;
                    }
                }
            }

            if ($matchedOption !== null) {
                $pushUnique($matchedOption);

                continue;
            }

            if (preg_match('/^(.*?)(?:\s*[;,]\s*(?:(?:dan|serta)\s+)?|\s+(?:dan|serta)\s+)(.*)$/iu', $remaining, $matches) === 1) {
                $pushUnique($matches[1]);
                $remaining = $matches[2];
            } else {
                $pushUnique($remaining);
                break;
            }
        }

        return $result;
    }

    /**
     * Merangkai daftar beberapa aspek menjadi frasa bahasa Indonesia yang rapi (aman untuk item mengandung koma / "dan").
     *
     * @param  list<string>  $items
     */
    public static function formatAspectList(array $items): ?string
    {
        $cleanItems = [];
        $seen = [];
        foreach ($items as $item) {
            $trimmed = trim(preg_replace('/\s+/', ' ', (string) $item) ?? '');
            $trimmed = rtrim($trimmed, ". \t\n\r\0\x0B");
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $cleanItems[] = $trimmed;
        }

        $count = count($cleanItems);
        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            return $cleanItems[0];
        }

        $hasComma = false;
        $hasDan = false;
        foreach ($cleanItems as $item) {
            if (str_contains($item, ',')) {
                $hasComma = true;
            }
            if (preg_match('/\bdan\b/iu', $item) === 1) {
                $hasDan = true;
            }
        }

        if ($count === 2) {
            $glue = ($hasComma || $hasDan) ? ', serta ' : ' dan ';

            return $cleanItems[0].$glue.$cleanItems[1];
        }

        $last = array_pop($cleanItems);
        if ($hasComma) {
            return implode('; ', $cleanItems).'; serta '.$last;
        }

        return implode(', ', $cleanItems).($hasDan ? ', serta ' : ', dan ').$last;
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'akademik_master_tahun_ajaran_id');
    }

    public function anggotaT2q(): BelongsTo
    {
        return $this->belongsTo(AnggotaT2q::class, 'anggota_t2q_id');
    }

    public function tahsinYaumiyah(): BelongsTo
    {
        return $this->belongsTo(YaumiyahTahsin::class, 'tahsin_yaumiyah_id');
    }

    public function tahfizhYaumiyah(): BelongsTo
    {
        return $this->belongsTo(YaumiyahTahfizh::class, 'tahfizh_yaumiyah_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
