<?php

namespace App\Http\Requests\Admin\TujuanPembelajaran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportTujuanPembelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawTa = $this->input('default_tahun_ajaran_id');
        if (is_string($rawTa)) {
            $trimmed = trim($rawTa);
            $this->merge([
                'default_tahun_ajaran_id' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120',
            ],
            'default_tahun_ajaran_id' => [
                'nullable',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File Excel atau CSV wajib dipilih.',
            'file.file' => 'Unggahan harus berupa file yang valid.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
            'default_tahun_ajaran_id.exists' => 'Tahun ajaran default tidak ditemukan.',
        ];
    }
}
