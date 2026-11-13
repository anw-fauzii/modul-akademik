<?php

namespace App\Http\Requests\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\KelompokPelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelompokPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawKode = $this->input('kode');
        $rawKelompok = $this->input('kelompok');
        $rawUrutan = $this->input('urutan');

        $this->merge([
            'kode' => is_string($rawKode)
                ? mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $rawKode)), 'UTF-8')
                : $rawKode,
            'kelompok' => is_string($rawKelompok)
                ? trim((string) preg_replace('/\s+/u', ' ', $rawKelompok))
                : $rawKelompok,
            'urutan' => is_string($rawUrutan) ? trim($rawUrutan) : $rawUrutan,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kelompokPelajaran = $this->route('kelompok_pelajaran');
        $kelompokPelajaranId = $kelompokPelajaran instanceof KelompokPelajaran
            ? $kelompokPelajaran->getKey()
            : $kelompokPelajaran;

        return [
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('akademik_pelajaran_kelompok_pelajaran', 'kode')->ignore($kelompokPelajaranId),
            ],
            'kelompok' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelajaran_kelompok_pelajaran', 'kelompok')->ignore($kelompokPelajaranId),
            ],
            'urutan' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kode' => 'Kode Kelompok',
            'kelompok' => 'Nama Kelompok Pelajaran',
            'urutan' => 'Urutan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode kelompok pelajaran wajib diisi.',
            'kode.max' => 'Kode kelompok pelajaran maksimal 50 karakter.',
            'kode.unique' => 'Kode kelompok pelajaran ini sudah terdaftar dalam sistem.',
            'kelompok.required' => 'Nama kelompok pelajaran wajib diisi.',
            'kelompok.max' => 'Nama kelompok pelajaran maksimal 255 karakter.',
            'kelompok.unique' => 'Nama kelompok pelajaran ini sudah terdaftar dalam sistem.',
            'urutan.required' => 'Urutan kelompok pelajaran wajib diisi.',
            'urutan.integer' => 'Urutan kelompok pelajaran harus berupa angka bulat.',
            'urutan.min' => 'Urutan kelompok pelajaran minimal 1.',
            'urutan.max' => 'Urutan kelompok pelajaran maksimal 9999.',
        ];
    }
}
