<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\ValidatesBatchMembershipRequest;
use App\Models\Admin\DataMaster\AnggotaJemputan;
use App\Models\Admin\DataMaster\Jemputan;
use App\Services\Admin\DataMaster\AnggotaJemputanService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnggotaJemputanRequest extends FormRequest
{
    use ValidatesBatchMembershipRequest;

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
        $isBatchStore = $this->isMethod('POST') && is_array($this->input('anggota_kelas_id'));

        $rules = [
            'akademik_master_jemputan_id' => [
                'required',
                'integer',
                'exists:akademik_master_jemputan,id',
            ],
            'pendaftaran' => [
                'required',
                'string',
                'max:100',
                Rule::in(AnggotaJemputan::PENDAFTARAN_OPTIONS),
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        $this->applyBatchMemberRules(
            $rules,
            'anggota_kelas_id',
            [
                'required',
                'integer',
                'exists:akademik_master_anggota_kelas,id',
            ],
            $isBatchStore
        );

        return $rules;
    }

    /**
     * Validasi lintas-kolom: tahun ajaran anggota kelas harus cocok & 1 siswa maksimal 1 jemputan per tahun ajaran.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateAnggotaKelasGroupConflict(
                    $validator,
                    'akademik_master_jemputan_id',
                    Jemputan::class,
                    app(AnggotaJemputanService::class),
                    'anggota_jemputan',
                    'jemputan'
                );
            },
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
            'anggota_kelas_id.required' => 'Anggota kelas siswa wajib dipilih.',
            'anggota_kelas_id.array' => 'Format pilihan anggota kelas tidak valid.',
            'anggota_kelas_id.min' => 'Pilih minimal satu siswa anggota kelas.',
            'anggota_kelas_id.integer' => 'Anggota kelas siswa tidak valid.',
            'anggota_kelas_id.exists' => 'Data anggota kelas siswa tidak ditemukan dalam database.',
            'anggota_kelas_id.*.required' => 'Anggota kelas siswa tidak boleh kosong.',
            'anggota_kelas_id.*.integer' => 'Salah satu anggota kelas yang dipilih tidak valid.',
            'anggota_kelas_id.*.distinct' => 'Terdapat duplikasi siswa dalam pilihan.',
            'anggota_kelas_id.*.exists' => 'Salah satu anggota kelas siswa tidak ditemukan dalam database.',
            'akademik_master_jemputan_id.required' => 'Layanan jemputan wajib dipilih.',
            'akademik_master_jemputan_id.integer' => 'Layanan jemputan tidak valid.',
            'akademik_master_jemputan_id.exists' => 'Layanan jemputan yang dipilih tidak valid.',
            'pendaftaran.required' => 'Status pendaftaran wajib dipilih.',
            'pendaftaran.in' => 'Status pendaftaran tidak valid.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}

