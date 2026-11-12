<?php

namespace App\Models\Admin\Informasi;

use App\Models\Admin\Informasi\Concerns\HasInformasiTahunAjaranAndJenis;
use App\Support\HtmlSanitizer;
use Database\Factories\Admin\Informasi\PengumumanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'akademik_master_tahun_ajaran_id',
    'judul',
    'isi',
    'jenis',
])]
#[Table('akademik_informasi_pengumuman')]
#[UseFactory(PengumumanFactory::class)]
class Pengumuman extends Model
{
    /** @use HasFactory<PengumumanFactory> */
    use HasFactory;

    use HasInformasiTahunAjaranAndJenis;

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'integer',
        ];
    }

    /**
     * Mengurutkan pengumuman dari yang terbaru dibuat.
     */
    #[Scope]
    protected function orderedLatest(Builder $query): Builder
    {
        return $query
            ->orderByDesc($query->qualifyColumn('created_at'))
            ->orderByDesc($query->qualifyColumn('id'));
    }

    /**
     * Ringkasan isi pengumuman satu baris untuk tampilan tabel.
     */
    protected function ringkasanIsi(): Attribute
    {
        return Attribute::get(function (): string {
            $plain = HtmlSanitizer::extractPlainText($this->isi);

            if ($plain === '') {
                return '-';
            }

            return Str::limit($plain, 140);
        });
    }

    /**
     * Konten HTML aman siap tampil (dari rich text editor maupun teks polos).
     */
    protected function isiAman(): Attribute
    {
        return Attribute::get(fn (): string => HtmlSanitizer::toSafeDisplayHtml($this->isi));
    }

    /**
     * Format waktu publikasi pengumuman dalam Bahasa Indonesia.
     */
    protected function tanggalDibuatFormatted(): Attribute
    {
        return Attribute::get(fn (): string => $this->created_at?->locale('id')->translatedFormat('d F Y H:i') ?? '-');
    }
}
