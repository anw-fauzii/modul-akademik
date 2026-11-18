<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\SekolahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable([
    'jenjang_kelas',
    'nama_yayasan',
    'nama_sekolah',
    'npsn',
    'nss',
    'alamat',
    'rt',
    'rw',
    'kelurahan',
    'kecamatan',
    'kabupaten',
    'provinsi',
    'kode_pos',
    'telepon',
    'email',
    'website',
    'status',
])]
#[Table('akademik_master_sekolah')]
#[UseFactory(SekolahFactory::class)]
class Sekolah extends Model
{
    /** @use HasFactory<SekolahFactory> */
    use HasFactory;

    /**
     * Daftar jenjang pendidikan yang tersedia untuk master profil sekolah.
     *
     * @var list<string>
     */
    public const array JENJANG_OPTIONS = ['PG', 'TK', 'SD', 'SMP'];

    /**
     * Nilai default atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => true,
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Mencocokkan profil sekolah aktif berdasarkan jenjang dari koleksi di memori (anti-N+1).
     *
     * @param  Collection<int, self>  $sekolahList
     */
    public static function resolveFromCollection(Collection $sekolahList, string $jenjangKelas): ?self
    {
        $normJenjang = strtoupper(trim($jenjangKelas));
        if ($normJenjang === '') {
            return null;
        }

        return $sekolahList->first(
            static fn (self $item): bool => (bool) $item->status
                && strtoupper(trim((string) $item->jenjang_kelas)) === $normJenjang
        );
    }

    /**
     * Kelas badge Bootstrap untuk jenjang sekolah.
     */
    protected function jenjangBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) $this->jenjang_kelas))) {
            'PG' => 'badge-info',
            'SD' => 'badge-success',
            'SMP' => 'badge-primary',
            'TK', 'PAUD' => 'badge-warning text-dark',
            default => 'badge-secondary',
        });
    }

    /**
     * Alamat lengkap terformat satu baris untuk kop dan halaman identitas PDF rapor.
     */
    protected function alamatLengkap(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = [];
            $jalan = trim((string) ($this->alamat ?? ''));
            if ($jalan !== '') {
                $parts[] = $jalan;
            }

            $rt = trim((string) ($this->rt ?? ''));
            $rw = trim((string) ($this->rw ?? ''));
            if ($rt !== '' || $rw !== '') {
                $parts[] = 'RT '.($rt !== '' ? $rt : '-').' / RW '.($rw !== '' ? $rw : '-');
            }

            foreach ([
                'kelurahan' => 'Kel. ',
                'kecamatan' => 'Kec. ',
                'kabupaten' => '',
                'provinsi' => 'Prov. ',
            ] as $field => $prefix) {
                $val = trim((string) ($this->$field ?? ''));
                if ($val !== '') {
                    $parts[] = $prefix.$val;
                }
            }

            $kodePos = trim((string) ($this->kode_pos ?? ''));
            if ($kodePos !== '') {
                $parts[] = $kodePos;
            }

            return $parts !== [] ? implode(', ', $parts) : '-';
        });
    }
}

