<?php

namespace App\Support;

use App\Models\Admin\DataMaster\AnggotaEkstrakurikuler;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Jemputan;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Models\Admin\DataPelengkap\BerkebutuhanKhusus;
use App\Models\Admin\DataPelengkap\JenjangPendidikan;
use App\Models\Admin\DataPelengkap\Pekerjaan;
use App\Models\Admin\DataPelengkap\Penghasilan;
use App\Models\Admin\DataPelengkap\Transportasi;
use App\Models\Admin\Informasi\AgendaSekolah;
use App\Models\Admin\Informasi\Pengumuman;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PenilaianFinalisasi;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Support\Sqids\SqidsCodec;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Throwable;

/**
 * Helper terpusat encode/decode Sqids ber-namespace per model (typeId + key)
 * agar token antar-entitas terisolasi dan ID numerik/string mentah pada URL ditolak.
 */
final class Sqid
{
    /**
     * Peta deterministik typeId unik untuk seluruh model routable aplikasi.
     *
     * @var array<int, class-string<Model>>
     */
    public const TYPE_MAP = [
        1 => AnggotaEkstrakurikuler::class,
        2 => AnggotaJemputan::class,
        3 => AnggotaKelas::class,
        4 => AnggotaT2q::class,
        5 => Ekstrakurikuler::class,
        6 => Jemputan::class,
        7 => Karyawan::class,
        8 => Kelas::class,
        9 => Sekolah::class,
        10 => Siswa::class,
        11 => TahunAjaran::class,
        12 => TargetCapaian::class,
        13 => Titimangsa::class,
        14 => BerkebutuhanKhusus::class,
        15 => JenjangPendidikan::class,
        16 => Pekerjaan::class,
        17 => Penghasilan::class,
        18 => Transportasi::class,
        19 => AgendaSekolah::class,
        20 => Pengumuman::class,
        21 => JadwalPelajaran::class,
        22 => JamPelajaran::class,
        23 => KelompokPelajaran::class,
        24 => MataPelajaran::class,
        25 => Kkm::class,
        26 => AspekTp::class,
        27 => SumatifBab::class,
        28 => TujuanPembelajaran::class,
        29 => RekapMapel::class,
        30 => PresensiSiswa::class,
        31 => YaumiyahTahfizh::class,
        32 => YaumiyahTahsin::class,
        33 => PenilaianFinalisasi::class,
    ];

    /**
     * Model yang menggunakan primary key string non-increment.
     *
     * @var array<class-string<Model>, true>
     */
    public const STRING_PK_MODELS = [
        Karyawan::class => true,
        Siswa::class => true,
    ];

    /**
     * Peta parameter query / konteks URL ke model yang bersangkutan.
     *
     * @var array<string, class-string<Model>>
     */
    public const QUERY_PARAM_MAP = [
        'tahun_ajaran_id' => TahunAjaran::class,
        'default_tahun_ajaran_id' => TahunAjaran::class,
        'kelas_id' => Kelas::class,
        'mata_pelajaran_id' => MataPelajaran::class,
        'ekstrakurikuler_id' => Ekstrakurikuler::class,
        'jemputan_id' => Jemputan::class,
        'kelompok_pelajaran_id' => KelompokPelajaran::class,
        'jam_pelajaran_id' => JamPelajaran::class,
        'tujuan_pembelajaran_id' => TujuanPembelajaran::class,
        'rekap_mapel_id' => RekapMapel::class,
        'from_kelas' => Kelas::class,
        'from_ekstrakurikuler' => Ekstrakurikuler::class,
        'from_jemputan' => Jemputan::class,
        'redirect_to_kelas' => Kelas::class,
        'redirect_to_ekstrakurikuler' => Ekstrakurikuler::class,
        'redirect_to_jemputan' => Jemputan::class,
        'redirect_kelas_id' => Kelas::class,
        'redirect_tahun_ajaran_id' => TahunAjaran::class,
        'karyawan_nipy' => Karyawan::class,
    ];

    /**
     * Nilai sentinel filter yang dibiarkan apa adanya dan tidak di-encode/decode.
     *
     * @var array<int, string>
     */
    public const SENTINEL_VALUES = [
        '',
        'all',
        '__unassigned__',
    ];

    private static ?SqidsCodec $codec = null;

    private static ?string $codecSignature = null;

    /**
     * @var array<class-string<Model>, int>|null
     */
    private static ?array $reverseTypeMap = null;

    /**
     * Menghapus cache codec internal (berguna saat test mengubah config).
     */
    public static function flushCache(): void
    {
        self::$codec = null;
        self::$codecSignature = null;
        self::$reverseTypeMap = null;
    }

    /**
     * Mengembalikan daftar sentinel filter yang dilewati saat encode/decode query.
     */
    public static function isSentinelValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_scalar($value)) {
            return false;
        }

        return in_array(trim((string) $value), self::SENTINEL_VALUES, true);
    }

    /**
     * Mengambil typeId numerik untuk model tertentu.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function typeIdFor(string $modelClass): int
    {
        if (self::$reverseTypeMap === null) {
            self::$reverseTypeMap = array_flip(self::TYPE_MAP);
        }

        $typeId = self::$reverseTypeMap[$modelClass] ?? null;

        if ($typeId === null) {
            throw new InvalidArgumentException("Model [{$modelClass}] belum terdaftar di App\\Support\\Sqid::TYPE_MAP.");
        }

        return $typeId;
    }

    /**
     * Menentukan apakah model menggunakan primary key string.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function usesStringKey(string $modelClass): bool
    {
        return isset(self::STRING_PK_MODELS[$modelClass]);
    }

    /**
     * Meng-encode model instance sesuai class-nya.
     */
    public static function encodeModel(Model $model): string
    {
        $encoded = self::encodeFor($model::class, $model->getKey());

        if ($encoded === null) {
            throw new InvalidArgumentException('Model belum memiliki primary key yang dapat di-encode sebagai Sqid.');
        }

        return $encoded;
    }

    /**
     * Meng-encode primary key mentah berdasarkan kelas model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function encodeFor(string $modelClass, int|string|null $key): ?string
    {
        if ($key === null) {
            return null;
        }

        if (is_string($key) && trim($key) === '') {
            return null;
        }

        $typeId = self::typeIdFor($modelClass);

        if (self::usesStringKey($modelClass)) {
            return self::encodeString($typeId, (string) $key);
        }

        if (is_string($key) && ! ctype_digit($key)) {
            return null;
        }

        $intKey = (int) $key;
        if ($intKey <= 0) {
            return null;
        }

        return self::encodeInt($typeId, $intKey);
    }

    /**
     * Meng-decode token Sqid menjadi primary key sesuai model, atau null jika tidak valid / salah tipe / bukan kanonik.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function decodeFor(string $modelClass, mixed $token): int|string|null
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $typeId = self::typeIdFor($modelClass);

        return self::usesStringKey($modelClass)
            ? self::decodeString($typeId, $token)
            : self::decodeInt($typeId, $token);
    }

    /**
     * Meng-encode nilai parameter query berdasarkan nama parameter, sambil menjaga sentinel.
     */
    public static function encodeQueryValue(string $paramName, mixed $value): mixed
    {
        $modelClass = self::QUERY_PARAM_MAP[$paramName] ?? null;
        if ($modelClass === null || self::isSentinelValue($value)) {
            return $value;
        }

        if ($value instanceof Model) {
            return self::encodeFor($modelClass, $value->getKey()) ?? $value;
        }

        $encoded = self::encodeFor($modelClass, is_scalar($value) ? (is_int($value) ? $value : trim((string) $value)) : null);

        return $encoded ?? $value;
    }

    /**
     * Meng-encode array parameter query / redirect (misal route('...', $params)).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function encodeQueryParams(array $params): array
    {
        $encoded = [];

        foreach ($params as $key => $value) {
            $encoded[$key] = is_string($key)
                ? self::encodeQueryValue($key, $value)
                : $value;
        }

        return $encoded;
    }

    /**
     * Meng-encode integer ID positif dengan namespace typeId.
     */
    public static function encodeInt(int $typeId, int $id): string
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID integer untuk Sqid harus positif, diterima: {$id}.");
        }

        return self::codec()->encode([$typeId, $id]);
    }

    /**
     * Meng-decode token Sqid integer dengan verifikasi typeId dan kanonikalitas penuh.
     */
    public static function decodeInt(int $typeId, string $token): ?int
    {
        if ($token === '') {
            return null;
        }

        try {
            $codec = self::codec();
            $numbers = $codec->decode($token);

            if (count($numbers) !== 2) {
                return null;
            }

            [$decodedTypeId, $decodedId] = $numbers;

            if ($decodedTypeId !== $typeId || $decodedId <= 0) {
                return null;
            }

            if ($codec->encode($numbers) !== $token) {
                return null;
            }

            return $decodedId;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Meng-encode string PK (seperti NIS/NIPY) secara lossless dengan chunk 4-byte agar tahan angka nol di depan dan tanda baca.
     */
    public static function encodeString(int $typeId, string $value): string
    {
        $length = strlen($value);

        if ($length === 0 || $length > 255) {
            throw new InvalidArgumentException('Panjang string key Sqid harus antara 1 sampai 255 byte.');
        }

        $chunks = [$typeId, $length];

        for ($i = 0; $i < $length; $i += 4) {
            $piece = str_pad(substr($value, $i, 4), 4, "\0", STR_PAD_RIGHT);
            $chunks[] = unpack('N', $piece)[1];
        }

        return self::codec()->encode($chunks);
    }

    /**
     * Meng-decode string PK dengan verifikasi typeId, panjang byte, padding kanonik, dan kanonikalitas token Sqid.
     */
    public static function decodeString(int $typeId, string $token): ?string
    {
        if ($token === '') {
            return null;
        }

        try {
            $codec = self::codec();
            $numbers = $codec->decode($token);

            if (count($numbers) < 3) {
                return null;
            }

            $decodedTypeId = $numbers[0];
            $length = $numbers[1];

            if ($decodedTypeId !== $typeId || $length <= 0 || $length > 255) {
                return null;
            }

            $expectedCount = 2 + (int) ceil($length / 4);
            if (count($numbers) !== $expectedCount) {
                return null;
            }

            if ($codec->encode($numbers) !== $token) {
                return null;
            }

            $raw = '';
            for ($i = 2; $i < $expectedCount; $i++) {
                $chunk = $numbers[$i];
                if ($chunk < 0 || $chunk > 0xFFFFFFFF) {
                    return null;
                }
                $raw .= pack('N', $chunk);
            }

            $decoded = substr($raw, 0, $length);
            $padding = substr($raw, $length);

            if ($decoded === '' || $padding !== str_repeat("\0", strlen($padding))) {
                return null;
            }

            return $decoded;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Mengembalikan instance SqidsCodec dengan alfabet deterministik dari config/APP_KEY.
     */
    private static function codec(): SqidsCodec
    {
        $configuredAlphabet = self::readConfig('sqids.alphabet', '');
        $defaultAlphabet = self::readConfig('sqids.default_alphabet', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $minLength = max(8, (int) self::readConfig('sqids.min_length', 8));
        $appKey = self::readConfig('app.key', self::readConfig('app.name', 'akademik-2'));

        $signature = $configuredAlphabet.'|'.$minLength.'|'.$appKey;

        if (self::$codec !== null && self::$codecSignature === $signature) {
            return self::$codec;
        }

        $alphabet = trim($configuredAlphabet) !== ''
            ? trim($configuredAlphabet)
            : self::deriveDeterministicAlphabet($defaultAlphabet, $appKey);

        self::$codec = new SqidsCodec($alphabet, $minLength);
        self::$codecSignature = $signature;

        return self::$codec;
    }

    /**
     * Mengacak alfabet dasar secara deterministik berdasarkan seed APP_KEY menggunakan Fisher-Yates.
     */
    private static function deriveDeterministicAlphabet(string $alphabet, string $seed): string
    {
        $chars = str_split($alphabet);
        $count = count($chars);
        $state = $seed;

        for ($i = $count - 1; $i > 0; $i--) {
            $state = hash('sha256', $state.'|'.$i, true);
            $uint = unpack('N', substr($state, 0, 4))[1];
            $j = $uint % ($i + 1);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    private static function readConfig(string $key, mixed $default): string
    {
        if (function_exists('app') && app()->bound('config')) {
            return (string) config($key, $default);
        }

        return (string) $default;
    }
}
