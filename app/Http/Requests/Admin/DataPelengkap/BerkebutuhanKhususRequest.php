<?php

namespace App\Http\Requests\Admin\DataPelengkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BerkebutuhanKhususRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $berkebutuhanKhususId = $this->route('berkebutuhan_khusus');

        return [
            'nama_berkebutuhan_khusus' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelengkap_berkebutuhan_khusus', 'nama_berkebutuhan_khusus')->ignore($berkebutuhanKhususId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_berkebutuhan_khusus.required' => 'Nama Kebutuhan Khusus wajib diisi.',
            'nama_berkebutuhan_khusus.unique'   => 'Nama kebutuhan khusus ini sudah ada dalam sistem.',
        ];
    }
}