<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Models\Admin\DataMaster\KomentarRapor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KomentarRaporRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $jenjang = strtoupper(trim((string) ($this->input('jenjang') ?? '')));

        $this->merge([
            'judul' => trim((string) preg_replace('/\s+/', ' ', (string) ($this->input('judul') ?? ''))),
            'isi_komentar' => trim((string) ($this->input('isi_komentar') ?? '')),
            'jenjang' => $jenjang !== '' ? $jenjang : null,
            'is_active' => $this->boolean('is_active', true),
            'urutan' => $this->input('urutan') !== null && $this->input('urutan') !== '' ? (int) $this->input('urutan') : 0,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var KomentarRapor|null $komentarRapor */
        $komentarRapor = $this->route('komentar_rapor');

        return [
            'judul' => [
                'required',
                'string',
                'max:120',
                Rule::unique('akademik_master_komentar_rapor', 'judul')->ignore($komentarRapor?->getKey()),
            ],
            'kategori' => [
                'required',
                'string',
                Rule::in(array_keys(KomentarRapor::KATEGORI_OPTIONS)),
            ],
            'jenjang' => ['nullable', 'string', Rule::in(['PG', 'TK', 'SD'])],
            'isi_komentar' => ['required', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'urutan' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul template komentar wajib diisi.',
            'judul.unique' => 'Judul template komentar sudah ada.',
            'kategori.required' => 'Kategori komentar wajib dipilih.',
            'isi_komentar.required' => 'Isi template komentar wajib diisi.',
            'isi_komentar.max' => 'Isi komentar maksimal 2.000 karakter.',
        ];
    }
}
