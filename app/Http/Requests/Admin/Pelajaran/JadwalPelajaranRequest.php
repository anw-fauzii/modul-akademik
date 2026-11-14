<?php

namespace App\Http\Requests\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JadwalPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawTa = $this->input('akademik_master_tahun_ajaran_id');
        $rawKelas = $this->input('akademik_master_kelas_id');
        $rawJam = $this->input('akademik_pelajaran_jam_pelajaran_id');
        $rawMapel = $this->input('akademik_pelajaran_mata_pelajaran_id');
        $rawGuru = $this->input('karyawan_nipy');
        $rawHari = $this->input('hari');
        $rawRuang = $this->input('ruang');
        $rawKeterangan = $this->input('keterangan');

        $normalized = [
            'akademik_master_tahun_ajaran_id' => is_string($rawTa) ? trim($rawTa) : $rawTa,
            'akademik_master_kelas_id' => is_string($rawKelas) ? trim($rawKelas) : $rawKelas,
            'akademik_pelajaran_jam_pelajaran_id' => is_string($rawJam) ? trim($rawJam) : $rawJam,
            'akademik_pelajaran_mata_pelajaran_id' => $this->normalizeNullableString($rawMapel),
            'karyawan_nipy' => $this->normalizeNullableString($rawGuru),
            'hari' => is_string($rawHari) ? trim($rawHari) : $rawHari,
            'ruang' => $this->normalizeCollapsedNullableText($rawRuang),
            'keterangan' => $this->normalizeCollapsedNullableText($rawKeterangan),
        ];

        if ($this->has('status')) {
            $rawStatus = $this->input('status');
            $parsedStatus = filter_var($rawStatus, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalized['status'] = $parsedStatus ?? $rawStatus;
        } else {
            $normalized['status'] = true;
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jadwalPelajaran = $this->route('jadwal_pelajaran');
        $jadwalPelajaranId = $jadwalPelajaran instanceof JadwalPelajaran
            ? $jadwalPelajaran->getKey()
            : $jadwalPelajaran;

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
            'akademik_master_kelas_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_kelas', 'id'),
            ],
            'akademik_pelajaran_jam_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_jam_pelajaran', 'id'),
                Rule::unique('akademik_pelajaran_jadwal_pelajaran', 'akademik_pelajaran_jam_pelajaran_id')
                    ->where(fn ($query) => $query
                        ->where('akademik_master_tahun_ajaran_id', $this->input('akademik_master_tahun_ajaran_id'))
                        ->where('akademik_master_kelas_id', $this->input('akademik_master_kelas_id'))
                        ->where('hari', $this->input('hari')))
                    ->ignore($jadwalPelajaranId),
            ],
            'akademik_pelajaran_mata_pelajaran_id' => [
                'nullable',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id'),
            ],
            'karyawan_nipy' => [
                'nullable',
                'string',
                'max:100',
                Rule::exists('kepegawaian_master_karyawan', 'nipy'),
            ],
            'hari' => [
                'required',
                'integer',
                Rule::in(array_keys(JadwalPelajaran::HARI_OPTIONS)),
            ],
            'ruang' => [
                'nullable',
                'string',
                'max:50',
            ],
            'keterangan' => [
                Rule::requiredIf(fn (): bool => blank($this->input('akademik_pelajaran_mata_pelajaran_id'))),
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Validasi lintas relasi & anti-bentrok guru setelah validasi kolom dasar lolos.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $jadwalPelajaran = $this->route('jadwal_pelajaran');
                $ignoreId = $jadwalPelajaran instanceof JadwalPelajaran
                    ? $jadwalPelajaran->getKey()
                    : $jadwalPelajaran;

                app(JadwalPelajaranService::class)->validateScheduleRules(
                    $validator,
                    $this->only([
                        'akademik_master_tahun_ajaran_id',
                        'akademik_master_kelas_id',
                        'akademik_pelajaran_jam_pelajaran_id',
                        'akademik_pelajaran_mata_pelajaran_id',
                        'karyawan_nipy',
                        'hari',
                        'status',
                    ]),
                    $ignoreId
                );
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran',
            'akademik_master_kelas_id' => 'Kelas',
            'akademik_pelajaran_jam_pelajaran_id' => 'Jam Pelajaran',
            'akademik_pelajaran_mata_pelajaran_id' => 'Mata Pelajaran',
            'karyawan_nipy' => 'Guru Pengampu',
            'hari' => 'Hari',
            'ruang' => 'Ruang',
            'keterangan' => 'Keterangan',
            'status' => 'Status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran tidak ditemukan.',
            'akademik_master_kelas_id.required' => 'Kelas wajib dipilih.',
            'akademik_master_kelas_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'akademik_pelajaran_jam_pelajaran_id.required' => 'Slot jam pelajaran wajib dipilih.',
            'akademik_pelajaran_jam_pelajaran_id.exists' => 'Jam pelajaran yang dipilih tidak ditemukan.',
            'akademik_pelajaran_jam_pelajaran_id.unique' => 'Slot jam pelajaran pada kelas dan hari tersebut sudah terisi.',
            'akademik_pelajaran_mata_pelajaran_id.exists' => 'Mata pelajaran yang dipilih tidak ditemukan.',
            'karyawan_nipy.exists' => 'Guru pengampu yang dipilih tidak ditemukan.',
            'hari.required' => 'Hari pembelajaran wajib dipilih.',
            'hari.in' => 'Hari pembelajaran hanya tersedia Senin sampai Jumat.',
            'ruang.max' => 'Nama ruang maksimal 50 karakter.',
            'keterangan.required' => 'Keterangan wajib diisi bila slot tidak memilih mata pelajaran (misal: Istirahat, Upacara, Makan, atau Pulang).',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
            'status.required' => 'Status jadwal wajib dipilih.',
            'status.boolean' => 'Status jadwal harus bernilai aktif atau nonaktif.',
        ];
    }

    private function normalizeNullableString(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeCollapsedNullableText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $trimmed === '' ? null : $trimmed;
    }
}
