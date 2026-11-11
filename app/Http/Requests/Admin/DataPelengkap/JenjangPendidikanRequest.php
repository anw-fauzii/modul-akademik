<?php

namespace App\Http\Requests\Admin\DataPelengkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JenjangPendidikanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jenjangPendidikanId = $this->route('jenjang_pendidikan');

        return [
            'nama_jenjang_pendidikan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelengkap_jenjang_pendidikan', 'nama_jenjang_pendidikan')->ignore($jenjangPendidikanId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_jenjang_pendidikan.required' => 'Nama Jenjang Pendidikan wajib diisi.',
            'nama_jenjang_pendidikan.unique'   => 'Nama jenjang pendidikan ini sudah ada dalam sistem.',
        ];
    }
}
