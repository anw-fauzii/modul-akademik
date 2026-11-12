<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\ValidatesBatchMembershipRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Services\Admin\DataMaster\AnggotaT2qService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnggotaT2qRequest extends FormRequest
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
     * Menormalisasi input tingkat khusus ketika user memilih opsi ketik manual.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('tingkat') === AnggotaT2q::TINGKAT_CUSTOM) {
            $this->merge([
                'tingkat' => trim((string) $this->input('tingkat_custom', '')),
            ]);
        }
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
            'tahun_ajaran_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'karyawan_nipy' => [
                'required',
                'string',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'tingkat' => [
                'required',
                'string',
                'max:50',
            ],
            'pendaftaran' => [
                'required',
                'string',
                'max:100',
                Rule::in(AnggotaT2q::PENDAFTARAN_OPTIONS),
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
     * Validasi lintas-kolom: tahun ajaran konsisten, 1 siswa maksimal 1 anggota T2Q per tahun ajaran,
     * dan kelengkapan tingkatan kelas apabila memilih mode tingkat otomatis.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['tahun_ajaran_id', 'anggota_kelas_id', 'anggota_kelas_id.*'])) {
                    return;
                }

                $anggotaKelasIds = array_values(array_unique(array_map(
                    'intval',
                    $this->extractMemberIdentifierList('anggota_kelas_id')
                )));

                $membershipService = app(AnggotaT2qService::class);
                $tahunAjaranId = $this->integer('tahun_ajaran_id') ?: null;

                if ($tahunAjaranId !== null) {
                    $valid = $this->validateAnggotaKelasConflictsInTahunAjaran(
                        $validator,
                        $anggotaKelasIds,
                        $tahunAjaranId,
                        $membershipService,
                        'anggota_t2q',
                        'anggota T2Q'
                    );

                    if (! $valid) {
                        return;
                    }
                } else {
                    $anggotaKelasRows = AnggotaKelas::query()
                        ->select(['id', 'siswa_nis', 'akademik_master_kelas_id'])
                        ->with(['kelas:id,akademik_master_tahun_ajaran_id,tingkatan_kelas'])
                        ->whereIn('id', $anggotaKelasIds)
                        ->get();

                    $seenStudentYears = [];
                    foreach ($anggotaKelasRows as $row) {
                        $rowTahunAjaranId = (int) ($row->kelas?->akademik_master_tahun_ajaran_id ?? 0);
                        if ($rowTahunAjaranId <= 0) {
                            $validator->errors()->add(
                                'anggota_kelas_id',
                                'Kelas pada anggota kelas yang dipilih belum terhubung ke tahun ajaran.'
                            );

                            return;
                        }

                        $studentYearKey = $row->siswa_nis.':'.$rowTahunAjaranId;
                        if (isset($seenStudentYears[$studentYearKey])) {
                            $validator->errors()->add(
                                'anggota_kelas_id',
                                "Siswa dengan NIS {$row->siswa_nis} tidak boleh dipilih dua kali dalam tahun ajaran yang sama."
                            );

                            return;
                        }
                        $seenStudentYears[$studentYearKey] = true;
                    }

                    $groupedByYear = $anggotaKelasRows->groupBy(
                        static fn (AnggotaKelas $row): int => (int) ($row->kelas?->akademik_master_tahun_ajaran_id ?? 0)
                    );

                    foreach ($groupedByYear as $yearId => $rows) {
                        $idsInYear = $rows->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all();
                        $valid = $this->validateAnggotaKelasConflictsInTahunAjaran(
                            $validator,
                            $idsInYear,
                            (int) $yearId,
                            $membershipService,
                            'anggota_t2q',
                            'anggota T2Q'
                        );

                        if (! $valid) {
                            return;
                        }
                    }
                }

                if ($this->input('tingkat') === AnggotaT2q::TINGKAT_AUTO) {
                    $missingTingkat = AnggotaKelas::query()
                        ->with(['kelas:id,tingkatan_kelas'])
                        ->whereIn('id', $anggotaKelasIds)
                        ->get()
                        ->contains(static fn (AnggotaKelas $row): bool => trim((string) $row->kelas?->tingkatan_kelas) === '');

                    if ($missingTingkat) {
                        $validator->errors()->add(
                            'tingkat',
                            'Tingkatan kelas untuk salah satu siswa belum tersedia sehingga tingkat T2Q wajib dipilih manual.'
                        );
                    }
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
            'tahun_ajaran_id.integer' => 'Tahun ajaran tidak valid.',
            'tahun_ajaran_id.exists' => 'Tahun ajaran yang dipilih tidak valid.',
            'anggota_kelas_id.required' => 'Anggota kelas siswa wajib dipilih.',
            'anggota_kelas_id.array' => 'Format pilihan anggota kelas tidak valid.',
            'anggota_kelas_id.min' => 'Pilih minimal satu siswa anggota kelas.',
            'anggota_kelas_id.integer' => 'Anggota kelas siswa tidak valid.',
            'anggota_kelas_id.exists' => 'Data anggota kelas siswa tidak ditemukan dalam database.',
            'anggota_kelas_id.*.required' => 'Anggota kelas siswa tidak boleh kosong.',
            'anggota_kelas_id.*.integer' => 'Salah satu anggota kelas yang dipilih tidak valid.',
            'anggota_kelas_id.*.distinct' => 'Terdapat duplikasi siswa dalam pilihan.',
            'anggota_kelas_id.*.exists' => 'Salah satu anggota kelas siswa tidak ditemukan dalam database.',
            'karyawan_nipy.required' => 'Guru pembimbing T2Q wajib dipilih.',
            'karyawan_nipy.string' => 'Guru pembimbing T2Q tidak valid.',
            'karyawan_nipy.exists' => 'Guru pembimbing T2Q tidak ditemukan dalam database.',
            'tingkat.required' => 'Tingkat T2Q wajib dipilih atau diisi.',
            'tingkat.string' => 'Tingkat T2Q tidak valid.',
            'tingkat.max' => 'Tingkat T2Q maksimal 50 karakter.',
            'pendaftaran.required' => 'Status pendaftaran wajib dipilih.',
            'pendaftaran.in' => 'Status pendaftaran tidak valid.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
