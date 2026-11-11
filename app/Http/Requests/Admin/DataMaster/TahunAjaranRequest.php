<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TahunAjaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tahunAjaranId = $this->route('tahun_ajaran');

        return [
            'nama_tahun_ajaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_master_tahun_ajaran', 'nama_tahun_ajaran')
                    ->where('semester', $this->input('semester'))
                    ->ignore($tahunAjaranId),
            ],
            'semester' => [
                'required',
                Rule::in(['1', 'mid 1', '2', 'mid 2']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_tahun_ajaran.required' => 'Nama Tahun Ajaran wajib diisi.',
            'nama_tahun_ajaran.unique'   => 'Kombinasi tahun ajaran dan semester ini sudah ada dalam sistem.',
            'semester.required'          => 'Semester wajib dipilih.',
            'semester.in'                => 'Semester tidak valid.',
        ];
    }
}
