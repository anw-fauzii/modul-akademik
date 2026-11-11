<?php

namespace App\Http\Requests\Admin\DataPelengkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PenghasilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $penghasilanId = $this->route('penghasilan');

        return [
            'nama_penghasilan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelengkap_penghasilan', 'nama_penghasilan')->ignore($penghasilanId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_penghasilan.required' => 'Nama Penghasilan wajib diisi.',
            'nama_penghasilan.unique'   => 'Nama penghasilan ini sudah ada dalam sistem.',
        ];
    }
}
