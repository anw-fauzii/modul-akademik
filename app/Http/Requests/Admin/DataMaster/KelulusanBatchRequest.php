<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\NormalizesMutasiRequestInputs;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Support\Sqid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class KelulusanBatchRequest extends FormRequest
{
    use NormalizesMutasiRequestInputs;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi ID SQID tahun ajaran, kelas, siswa_nis[], dan map nomor_ijazah[nis].
     */
    protected function prepareForValidation(): void
    {
        $rawIjazah = $this->input('nomor_ijazah', []);
        $normalizedIjazah = [];

        if (is_array($rawIjazah)) {
            foreach ($rawIjazah as $key => $value) {
                $keyStr = trim((string) $key);
                if ($keyStr === '') {
                    continue;
                }
                $decodedKey = Sqid::decodeFor(Siswa::class, $keyStr);
                $nisKey = $decodedKey !== null ? (string) $decodedKey : $keyStr;
                $cleanVal = $this->collapseWhitespace($value);
                if ($cleanVal !== null) {
                    $normalizedIjazah[$nisKey] = $cleanVal;
                }
            }
        }

        $this->merge([
            'akademik_master_tahun_ajaran_id' => $this->decodeIntegerIdOrRaw(
                $this->input('akademik_master_tahun_ajaran_id'),
                TahunAjaran::class
            ),
            'kelas_id' => $this->decodeIntegerIdOrRaw(
                $this->input('kelas_id'),
                Kelas::class
            ),
            'siswa_nis' => $this->normalizeSiswaNisList($this->input('siswa_nis', [])),
            'nomor_surat' => $this->collapseWhitespace($this->input('nomor_surat')),
            'alasan' => $this->collapseWhitespace($this->input('alasan')),
            'nomor_ijazah' => $normalizedIjazah,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'kelas_id' => [
                'required',
                'integer',
                'exists:akademik_master_kelas,id',
            ],
            'siswa_nis' => ['required', 'array', 'min:1'],
            'siswa_nis.*' => [
                'required',
                'string',
                'max:50',
                'distinct',
                'exists:akademik_master_siswa,nis',
            ],
            'tanggal_efektif' => ['required', 'date'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'alasan' => ['nullable', 'string', 'max:1000'],
            'nomor_ijazah' => ['nullable', 'array'],
            'nomor_ijazah.*' => ['nullable', 'string', 'max:100'],
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
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran kelulusan wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran kelulusan tidak ditemukan.',
            'kelas_id.required' => 'Kelas akhir wajib dipilih.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'siswa_nis.required' => 'Ceklis minimal satu siswa yang dinyatakan lulus.',
            'siswa_nis.min' => 'Ceklis minimal satu siswa yang dinyatakan lulus.',
            'siswa_nis.*.exists' => 'Salah satu NIS siswa tidak ditemukan di database.',
            'tanggal_efektif.required' => 'Tanggal kelulusan / SK wajib diisi.',
            'tanggal_efektif.date' => 'Format tanggal kelulusan tidak valid.',
            'nomor_surat.max' => 'Nomor SK Kelulusan maksimal 100 karakter.',
            'nomor_ijazah.*.max' => 'Nomor Ijazah / SKL maksimal 100 karakter.',
        ];
    }
}

