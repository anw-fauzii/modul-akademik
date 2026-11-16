<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AspekTpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_MID))),
            'nama_aspek' => trim((string) $this->input('nama_aspek', '')),
            'teknik_penilaian' => filled($this->input('teknik_penilaian'))
                ? strtolower(trim((string) $this->input('teknik_penilaian')))
                : null,
            'bobot' => filled($this->input('bobot')) ? (int) $this->input('bobot') : 100,
            'masuk_hitung_na_tp' => $this->has('masuk_hitung_na_tp')
                ? $this->boolean('masuk_hitung_na_tp')
                : true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tujuan_pembelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_tujuan_pembelajaran', 'id'),
            ],
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'nama_aspek' => ['required', 'string', 'max:100'],
            'teknik_penilaian' => ['nullable', 'string', Rule::in(array_keys(AspekTp::TEKNIK_OPTIONS))],
            'bobot' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'masuk_hitung_na_tp' => ['required', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'kelas_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_aspek.required' => 'Nama aspek penilaian wajib diisi (mis. Pemahaman, Bacaan, Makhroj).',
            'nama_aspek.max' => 'Nama aspek maksimal 100 karakter.',
        ];
    }
}
