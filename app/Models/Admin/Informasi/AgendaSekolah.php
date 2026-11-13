<?php

namespace App\Models\Admin\Informasi;

use App\Models\Admin\Informasi\Concerns\HasInformasiTahunAjaranAndJenis;
use App\Models\Concerns\HasUnitMetadata;
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
    use HasUnitMetadata;

    /**
     * Daftar unit sekolah yang tersedia untuk agenda sekolah.
     *
     * @var list<string>
     */
    public const array UNIT_OPTIONS = ['Semua Unit', 'PG', 'TK', 'SD'];

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
}
