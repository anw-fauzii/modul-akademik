<?php

namespace App\Http\Requests\GuruT2q;

use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRaporT2qRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'tingkat' => ['nullable', 'string', 'max:20'],
            'tahsin' => ['nullable', 'array'],
            'tahsin.*' => ['array'],
            'tahsin.*.keunggulan' => ['nullable', 'string', 'max:255'],
            'tahsin.*.peningkatan' => ['nullable', 'string', 'max:255'],
            'tahsin.*.deskripsi' => ['nullable', 'string', 'max:1500'],
            'tahfizh' => ['nullable', 'array'],
            'tahfizh.*' => ['array'],
            'tahfizh.*.keunggulan' => ['nullable', 'string', 'max:255'],
            'tahfizh.*.peningkatan' => ['nullable', 'string', 'max:255'],
            'tahfizh.*.deskripsi' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.in' => 'Periode rapor T2Q harus Mid Semester atau Semester.',
            'tahsin.*.deskripsi.max' => 'Deskripsi Tahsin maksimal 1.500 karakter.',
            'tahfizh.*.deskripsi.max' => 'Deskripsi Tahfidz maksimal 1.500 karakter.',
        ];
    }
}
