<?php

namespace App\Models\Admin\Pelajaran;

use App\Models\Concerns\HasUnitMetadata;
use Database\Factories\Admin\Pelajaran\JamPelajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'jam_ke',
    'unit',
    'jam_mulai',
    'jam_selesai',
    'keterangan',
])]
#[Table('akademik_pelajaran_jam_pelajaran')]
#[UseFactory(JamPelajaranFactory::class)]
class JamPelajaran extends Model
{
    /** @use HasFactory<JamPelajaranFactory> */
    use HasFactory;

    use HasUnitMetadata;

    /**
     * Daftar unit pendidikan yang tersedia untuk jam pelajaran.
     *
     * @var list<string>
     */
    public const array UNIT_OPTIONS = ['PG', 'TK', 'SD'];

    /**
     * Relasi utama yang selalu di-eager load untuk mencegah N+1.
     *
     * @var list<string>
     */
    public const array INDEX_RELATIONS = [];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jam_ke' => 'integer',
        ];
    }

    /**
     * Eager load relasi utama dengan kolom minimal untuk mencegah N+1.
     */
    #[Scope]
    protected function withMasterRelations(Builder $query): Builder
    {
        return $query->with(self::INDEX_RELATIONS);
    }

    /**
     * Mengurutkan jam pelajaran berdasarkan unit, jam ke, jam mulai, dan id.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('unit'))
            ->orderBy($query->qualifyColumn('jam_ke'))
            ->orderBy($query->qualifyColumn('jam_mulai'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Format jam mulai agar selalu tampil ringkas dalam format H:i (misal 07:30).
     */
    protected function jamMulaiFormatted(): Attribute
    {
        return Attribute::get(fn (): string => self::formatTimeShort($this->jam_mulai));
    }

    /**
     * Format jam selesai agar selalu tampil ringkas dalam format H:i (misal 08:05).
     */
    protected function jamSelesaiFormatted(): Attribute
    {
        return Attribute::get(fn (): string => self::formatTimeShort($this->jam_selesai));
    }

    /**
     * Format rentang waktu mulai sampai selesai (misal 07:30 - 08:05).
     */
    protected function rentangWaktu(): Attribute
    {
        return Attribute::get(function (): string {
            $mulai = $this->jam_mulai_formatted;
            $selesai = $this->jam_selesai_formatted;

            if ($mulai === '-' && $selesai === '-') {
                return '-';
            }

            return "{$mulai} - {$selesai}";
        });
    }

    /**
     * Durasi jam pelajaran dalam satuan menit.
     */
    protected function durasiMenit(): Attribute
    {
        return Attribute::get(function (): ?int {
            $mulaiMinutes = self::parseTimeToMinutes($this->jam_mulai);
            $selesaiMinutes = self::parseTimeToMinutes($this->jam_selesai);

            if ($mulaiMinutes === null || $selesaiMinutes === null || $selesaiMinutes <= $mulaiMinutes) {
                return null;
            }

            return $selesaiMinutes - $mulaiMinutes;
        });
    }

    /**
     * Label durasi jam pelajaran yang ramah dibaca (misal "35 menit").
     */
    protected function durasiLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $minutes = $this->durasi_menit;

            return $minutes !== null ? "{$minutes} menit" : '-';
        });
    }

    public static function formatTimeShort(mixed $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '-';
        }

        if (preg_match('/^(\d{2}:\d{2})/', $raw, $matches) === 1) {
            return $matches[1];
        }

        return $raw;
    }

    private static function parseTimeToMinutes(mixed $value): ?int
    {
        $short = self::formatTimeShort($value);
        if (preg_match('/^(\d{2}):(\d{2})$/', $short, $matches) !== 1) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }

    /**
     * Relasi ke daftar Jadwal Pelajaran yang menggunakan slot jam ini.
     */
    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'akademik_pelajaran_jam_pelajaran_id');
    }
}
