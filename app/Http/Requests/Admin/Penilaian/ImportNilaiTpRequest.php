<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportNilaiTpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_MID))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun_ajaran_id' => ['nullable', 'integer', Rule::exists('akademik_master_tahun_ajaran', 'id')],
            'kelas_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_kelas', 'id')->where('jenjang_kelas', Kkm::TARGET_UNIT),
            ],
            'mata_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id')->where('unit', Kkm::TARGET_UNIT),
            ],
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file Excel/CSV nilai TP terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
