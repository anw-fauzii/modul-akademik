<?php

namespace App\Http\Requests\Admin\DataPelengkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransportasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transportasiId = $this->route('transportasi');

        return [
            'nama_transportasi' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelengkap_transportasi', 'nama_transportasi')->ignore($transportasiId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_transportasi.required' => 'Nama Transportasi wajib diisi.',
            'nama_transportasi.unique'   => 'Nama transportasi ini sudah ada dalam sistem.',
        ];
    }
}
