<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Foundation\Http\FormRequest;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'karyawan_nipy' => [
                'nullable',
                'string',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'pendamping_nipy' => [
                'nullable',
                'string',
                'exists:kepegawaian_master_karyawan,nipy',
                'different:karyawan_nipy',
            ],
            'tingkatan_kelas' => [
                'required',
                'string',
                'max:50',
            ],
            'nama_kelas' => [
                'required',
                'string',
                'max:255',
            ],
            'jenjang_kelas' => [
                'required',
                'string',
                'max:100',
            ],
            'romawi' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun Ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists'   => 'Tahun Ajaran yang dipilih tidak valid.',
            'karyawan_nipy.exists'                     => 'Guru Wali Kelas tidak ditemukan dalam database.',
            'pendamping_nipy.exists'                   => 'Guru Pendamping tidak ditemukan dalam database.',
            'pendamping_nipy.different'                => 'Guru Pendamping tidak boleh sama dengan Guru Wali Kelas.',
            'tingkatan_kelas.required'                 => 'Tingkatan Kelas wajib diisi.',
            'nama_kelas.required'                      => 'Nama Kelas wajib diisi.',
            'jenjang_kelas.required'                   => 'Jenjang Kelas wajib diisi.',
            'romawi.required'                          => 'Format Romawi kelas wajib diisi.',
        ];
    }
}
