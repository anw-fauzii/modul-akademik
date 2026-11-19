<?php

namespace App\Models\Concerns;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\Surah;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasYaumiyahT2q
{
    /**
     * Cast dasar yang dipakai bersama oleh pencatatan yaumiyah Tahsin dan Tahfizh.
     *
     * @return array<string, string>
     */
    protected function yaumiyahBaseCasts(): array
    {
        return [
            'anggota_t2q_id' => 'integer',
            'akademik_master_t2q_surah_id' => 'integer',
            'akademik_master_t2q_angka_arab_id' => 'integer',
            'tanggal' => 'date',
            'nilai' => 'float',
        ];
    }

    /**
     * Memformat nilai numerik tanpa angka nol desimal berlebih.
     */
    protected function nilaiFormatted(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->nilai === null) {
                return '-';
            }

            return rtrim(rtrim(number_format((float) $this->nilai, 2, '.', ''), '0'), '.');
        });
    }

    /**
     * Memetakan nilai numerik ke tier capaian standar T2Q secara terpusat.
     */
    public static function tierFromScore(float $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 75 => 'good',
            $score >= 60 => 'fair',
            default => 'poor',
        };
    }

    /**
     * Memetakan nilai numerik model ke tier capaian standar T2Q.
     */
    private function resolveNilaiTier(): string
    {
        return self::tierFromScore((float) ($this->nilai ?? 0));
    }

    /**
     * Kunci tier nilai ('excellent', 'good', 'fair', 'poor') untuk konsistensi UI & rekap.
     */
    protected function nilaiTier(): Attribute
    {
        return Attribute::get(fn (): string => $this->resolveNilaiTier());
    }

    /**
     * Memetakan kunci tier nilai ke label predikat standar T2Q.
     */
    public static function labelForTier(string $tier): string
    {
        return match ($tier) {
            'excellent' => 'Sangat Berkembang',
            'good' => 'Berkembang Sesuai Harapan',
            'fair' => 'Sedang Berkembang',
            'poor' => 'Mulai Berkembang',
            default => 'Belum Ada Data',
        };
    }

    /**
     * Predikat deskriptif singkat berdasarkan rentang nilai T2Q.
     */
    protected function nilaiLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::labelForTier($this->resolveNilaiTier()));
    }

    /**
     * Kelas badge Bootstrap untuk rentang nilai setoran T2Q.
     */
    protected function nilaiBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->resolveNilaiTier()) {
            'excellent' => 'badge-success',
            'good' => 'badge-primary',
            'fair' => 'badge-warning text-dark',
            default => 'badge-danger',
        });
    }

    /**
     * Ringkasan capaian setoran yang aman dari lazy loading untuk preview riwayat terakhir.
     */
    protected function ringkasanCapaian(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = [];

            if (method_exists($this, 'daftarJilid') && $this->relationLoaded('daftarJilid') && $this->daftarJilid !== null) {
                $parts[] = (string) $this->daftarJilid->jilid_latin;
            }

            $surah = $this->relationLoaded('surah') ? $this->surah : null;
            if ($surah !== null && filled($surah->nama_surah)) {
                $parts[] = (string) $surah->nama_surah;
            }

            $angka = $this->relationLoaded('angkaArab') ? $this->angkaArab : null;
            if ($angka !== null && filled($angka->angka_latin)) {
                $isQuran = ($surah !== null)
                    || (method_exists($this, 'daftarJilid')
                        && $this->relationLoaded('daftarJilid')
                        && strtolower(trim((string) ($this->daftarJilid?->jenis ?? ''))) === 'quran');
                $unit = $isQuran ? 'Ayat' : 'Hal.';
                $arabLabel = filled($angka->angka_arab) ? " ({$angka->angka_arab})" : '';
                $parts[] = "{$unit} {$angka->angka_latin}{$arabLabel}";
            }

            return $parts === [] ? '-' : implode(' • ', $parts);
        });
    }

    /**
     * Eager load relasi utama minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(static::MASTER_RELATIONS);
    }

    /**
     * Memfilter entri yaumiyah berdasarkan tanggal tertentu (Y-m-d).
     */
    #[Scope]
    protected function forTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate($query->qualifyColumn('tanggal'), $tanggal);
    }

    /**
     * Memfilter entri yaumiyah dalam rentang tanggal inklusif (Y-m-d).
     */
    #[Scope]
    protected function forDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        $column = $query->qualifyColumn('tanggal');

        return $query
            ->whereDate($column, '>=', $startDate)
            ->whereDate($column, '<=', $endDate);
    }

    /**
     * Memfilter entri yaumiyah untuk sekumpulan ID anggota T2Q.
     *
     * @param  list<int>  $anggotaT2qIds
     */
    #[Scope]
    protected function forAnggotaT2qIds(Builder $query, array $anggotaT2qIds): Builder
    {
        if ($anggotaT2qIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->qualifyColumn('anggota_t2q_id'), $anggotaT2qIds);
    }

    /**
     * Relasi ke anggota bimbingan T2Q.
     *
     * @return BelongsTo<AnggotaT2q, $this>
     */
    public function anggotaT2q(): BelongsTo
    {
        return $this->belongsTo(AnggotaT2q::class, 'anggota_t2q_id');
    }

    /**
     * Relasi ke master surah Al-Qur'an.
     *
     * @return BelongsTo<Surah, $this>
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'akademik_master_t2q_surah_id');
    }

    /**
     * Relasi ke master angka Arab (nomor halaman / ayat).
     *
     * @return BelongsTo<AngkaArab, $this>
     */
    public function angkaArab(): BelongsTo
    {
        return $this->belongsTo(AngkaArab::class, 'akademik_master_t2q_angka_arab_id');
    }
}
