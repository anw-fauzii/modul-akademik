<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EkstrakurikulerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ekstrakurikuler = $this->route('ekstrakurikuler');

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'karyawan_nipy' => [
                'nullable',
                'string',
                'max:255',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'nama_ekstrakurikuler' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_master_ekstrakuler', 'nama_ekstrakurikuler')
                    ->where(fn ($query) => $query
                        ->where('akademik_master_tahun_ajaran_id', $this->input('akademik_master_tahun_ajaran_id'))
                        ->where('unit', $this->input('unit'))
                    )
                    ->ignore($ekstrakurikuler),
            ],
            'biaya' => [
                'required',
                'integer',
                'min:0',
            ],
            'unit' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun Ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.integer' => 'Tahun Ajaran tidak valid.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun Ajaran yang dipilih tidak valid.',
            'karyawan_nipy.exists' => 'Pembina / Pelatih tidak ditemukan dalam database.',
            'nama_ekstrakurikuler.required' => 'Nama Ekstrakurikuler wajib diisi.',
            'nama_ekstrakurikuler.max' => 'Nama Ekstrakurikuler maksimal 255 karakter.',
            'nama_ekstrakurikuler.unique' => 'Ekstrakurikuler dengan nama tersebut sudah ada pada unit dan tahun ajaran yang dipilih.',
            'biaya.required' => 'Biaya Ekstrakurikuler wajib diisi.',
            'biaya.integer' => 'Biaya Ekstrakurikuler harus berupa angka bulat.',
            'biaya.min' => 'Biaya Ekstrakurikuler tidak boleh bernilai negatif.',
            'unit.required' => 'Unit wajib dipilih.',
            'unit.max' => 'Unit maksimal 100 karakter.',
        ];
    }
}
