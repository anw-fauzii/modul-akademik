<?php

namespace App\Http\Requests\Admin\Informasi;

use App\Models\Admin\Informasi\Pengumuman;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawJudul = $this->input('judul');
        $rawIsi = $this->input('isi');
        $normalizedIsi = $rawIsi;

        if (is_string($rawIsi)) {
            $sanitizedIsi = HtmlSanitizer::sanitize($rawIsi);
            $normalizedIsi = HtmlSanitizer::extractPlainText($sanitizedIsi) === ''
                ? ''
                : $sanitizedIsi;
        }

        $this->merge([
            'judul' => is_string($rawJudul)
                ? trim((string) preg_replace('/\s+/u', ' ', $rawJudul))
                : $rawJudul,
            'isi' => $normalizedIsi,
            // Aplikasi akademik khusus mengelola pengumuman bertipe siswa.
            'jenis' => Pengumuman::JENIS_SISWA,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
            'judul' => [
                'required',
                'string',
                'max:255',
            ],
            'isi' => [
                'required',
                'string',
            ],
            'jenis' => [
                'required',
                'string',
                Rule::in([Pengumuman::JENIS_SISWA]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran',
            'judul' => 'Judul Pengumuman',
            'isi' => 'Isi Pengumuman',
            'jenis' => 'Jenis Pengumuman',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.integer' => 'Tahun ajaran tidak valid.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran yang dipilih tidak ditemukan.',
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'judul.max' => 'Judul pengumuman maksimal 255 karakter.',
            'isi.required' => 'Isi pengumuman wajib diisi.',
            'jenis.required' => 'Jenis pengumuman wajib diisi.',
            'jenis.in' => 'Jenis pengumuman pada aplikasi akademik hanya diperbolehkan untuk siswa.',
        ];
    }
}
