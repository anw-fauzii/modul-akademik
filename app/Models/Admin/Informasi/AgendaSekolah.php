<?php

namespace App\Models\Admin\Informasi;

use App\Models\Admin\Informasi\Concerns\HasInformasiTahunAjaranAndJenis;
use Database\Factories\Admin\Informasi\AgendaSekolahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'nama_agenda',
    'tanggal',
    'jam',
    'unit',
    'jenis',
])]
#[Table('akademik_informasi_agenda_sekolah')]
#[UseFactory(AgendaSekolahFactory::class)]
class AgendaSekolah extends Model
{
    /** @use HasFactory<AgendaSekolahFactory> */
    use HasFactory;

    use HasInformasiTahunAjaranAndJenis;

    /**
     * Daftar unit sekolah yang tersedia untuk agenda sekolah.
     *
     * @var list<string>
     */
    public const array UNIT_OPTIONS = ['Semua Unit', 'TK', 'SD', 'SMP', 'SMA'];

    /**
     * Membandingkan urutan unit sesuai prioritas tampilan sekolah.
     */
    public static function compareUnitOrder(string $a, string $b): int
    {
        $order = array_map('strtoupper', self::UNIT_OPTIONS);
        $normA = strtoupper(trim($a));
        $normB = strtoupper(trim($b));

        $posA = array_search($normA, $order, true);
        $posB = array_search($normB, $order, true);

        if ($posA !== false && $posB !== false) {
            return $posA <=> $posB;
        }

        if ($posA !== false) {
            return -1;
        }

        if ($posB !== false) {
            return 1;
        }

        return $normA <=> $normB;
    }

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
            'tanggal' => 'date',
        ];
    }

    /**
     * Mengurutkan agenda secara kronologis berdasarkan tanggal dan jam pelaksanaan.
     */
    #[Scope]
    protected function orderedChronologically(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('tanggal'))
            ->orderBy($query->qualifyColumn('jam'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Format jam agar selalu tampil ringkas dalam format H:i (misal 07:30).
     */
    protected function jamFormatted(): Attribute
    {
        return Attribute::get(function (): string {
            $rawJam = trim((string) ($this->jam ?? ''));
            if ($rawJam === '') {
                return '-';
            }

            if (preg_match('/^(\d{2}:\d{2})/', $rawJam, $matches) === 1) {
                return $matches[1];
            }

            return $rawJam;
        });
    }

    /**
     * Format tanggal agenda dalam Bahasa Indonesia (contoh: 12 November 2026).
     */
    protected function tanggalFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->tanggal?->locale('id')->translatedFormat('d F Y') ?? '-');
    }

    /**
     * Nama hari pelaksanaan agenda dalam Bahasa Indonesia (contoh: Kamis).
     */
    protected function hariFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->tanggal?->locale('id')->translatedFormat('l') ?? '-');
    }

    /**
     * Kelas badge Bootstrap untuk label unit agenda sekolah.
     */
    protected function unitBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) $this->unit))) {
            'SEMUA UNIT', 'UMUM' => 'badge-dark',
            'SD' => 'badge-success',
            'SMP' => 'badge-primary',
            'TK', 'PAUD' => 'badge-warning text-dark',
            'SMA', 'SMK' => 'badge-info',
            default => 'badge-secondary',
        });
    }
}
