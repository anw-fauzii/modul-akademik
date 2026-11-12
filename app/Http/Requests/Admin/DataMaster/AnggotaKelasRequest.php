<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\ValidatesBatchMembershipRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Services\Admin\DataMaster\AnggotaKelasService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnggotaKelasRequest extends FormRequest
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
        $isBatchStore = $this->isMethod('POST') && is_array($this->input('siswa_nis'));

        $rules = [
            'akademik_master_kelas_id' => [
                'required',
                'integer',
                'exists:akademik_master_kelas,id',
            ],
            'pendaftaran' => [
                'required',
                'string',
                'max:100',
                Rule::in(AnggotaKelas::PENDAFTARAN_OPTIONS),
            ],
        ];

        $this->applyBatchMemberRules(
            $rules,
            'siswa_nis',
            [
                'required',
                'string',
                'max:50',
                'exists:akademik_master_siswa,nis',
            ],
            $isBatchStore
        );

        return $rules;
    }

    /**
     * Validasi lintas-kolom untuk mencegah 1 siswa terdaftar di 2 kelas pada tahun ajaran/semester yang sama.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['akademik_master_kelas_id', 'siswa_nis', 'siswa_nis.*'])) {
                    return;
                }

                $tahunAjaranId = Kelas::query()
                    ->whereKey($this->integer('akademik_master_kelas_id'))
                    ->value('akademik_master_tahun_ajaran_id');

                if ($tahunAjaranId === null) {
                    return;
                }

                $nisList = $this->extractMemberIdentifierList('siswa_nis');

                /** @var AnggotaKelas|null $anggotaKelas */
                $anggotaKelas = $this->route('anggota_kelas');

                $conflicts = app(AnggotaKelasService::class)->findConflictingNisInTahunAjaran(
                    $nisList,
                    (int) $tahunAjaranId,
                    $anggotaKelas?->id
                );

                if ($conflicts !== []) {
                    $validator->errors()->add(
                        'siswa_nis',
                        'Siswa dengan NIS '.implode(', ', $conflicts).' sudah terdaftar di kelas pada tahun ajaran dan semester yang dipilih.'
                    );
                }
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
            'siswa_nis.required' => 'Siswa wajib dipilih.',
            'siswa_nis.array' => 'Format pilihan siswa tidak valid.',
            'siswa_nis.min' => 'Pilih minimal satu siswa.',
            'siswa_nis.exists' => 'Data siswa tidak ditemukan dalam database.',
            'siswa_nis.*.required' => 'NIS siswa tidak boleh kosong.',
            'siswa_nis.*.distinct' => 'Terdapat duplikasi siswa dalam pilihan.',
            'siswa_nis.*.exists' => 'Salah satu siswa yang dipilih tidak ditemukan dalam database.',
            'akademik_master_kelas_id.required' => 'Kelas wajib dipilih.',
            'akademik_master_kelas_id.integer' => 'Kelas tidak valid.',
            'akademik_master_kelas_id.exists' => 'Kelas yang dipilih tidak valid.',
            'pendaftaran.required' => 'Jenis pendaftaran wajib dipilih.',
            'pendaftaran.in' => 'Jenis pendaftaran tidak valid.',
        ];
    }
}
