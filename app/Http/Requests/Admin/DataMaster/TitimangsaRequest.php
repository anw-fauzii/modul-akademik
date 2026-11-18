<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Models\Admin\DataMaster\Titimangsa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TitimangsaRequest extends FormRequest
{
    /**
     * Menentukan apakah user berwenang mengirim request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi input jenjang, tingkatan (default '*'), dan kolom opsional sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $tingkatanScope = trim((string) $this->input('tingkatan_scope', ''));
        $rawTingkatan = trim((string) $this->input('tingkatan_kelas', ''));

        if ($tingkatanScope === 'semua' || $rawTingkatan === '') {
            $normalizedTingkatan = Titimangsa::TINGKATAN_SEMUA;
        } else {
            $normalizedTingkatan = $rawTingkatan;
        }

        $nipy = trim((string) $this->input('kepala_sekolah_nipy', ''));
        $snapshot = trim((string) $this->input('nama_kepala_sekolah_snapshot', ''));

        $this->merge([
            'jenjang_kelas' => strtoupper(trim((string) $this->input('jenjang_kelas', ''))),
            'tingkatan_kelas' => $normalizedTingkatan,
            'periode' => strtolower(trim((string) $this->input('periode', ''))),
            'tempat' => trim((string) $this->input('tempat', '')),
            'kepala_sekolah_nipy' => $nipy !== '' ? $nipy : null,
            'nama_kepala_sekolah_snapshot' => $snapshot !== '' ? $snapshot : null,
            'jabatan_kepala_sekolah' => trim((string) $this->input('jabatan_kepala_sekolah', '')) ?: Titimangsa::DEFAULT_JABATAN,
            'status' => $this->boolean('status', true),
        ]);
    }

    /**
     * Aturan validasi data master titimangsa & pejabat penandatangan rapor.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $titimangsa = $this->route('titimangsa');

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'jenjang_kelas' => [
                'required',
                'string',
                'max:20',
            ],
            'tingkatan_kelas' => [
                'required',
                'string',
                'max:20',
            ],
            'periode' => [
                'required',
                'string',
                Rule::in(array_keys(Titimangsa::PERIODE_OPTIONS)),
                Rule::unique('akademik_master_titimangsa', 'periode')
                    ->where(fn ($query) => $query
                        ->where('akademik_master_tahun_ajaran_id', $this->input('akademik_master_tahun_ajaran_id'))
                        ->where('jenjang_kelas', $this->input('jenjang_kelas'))
                        ->where('tingkatan_kelas', $this->input('tingkatan_kelas'))
                    )
                    ->ignore($titimangsa),
            ],
            'tempat' => [
                'required',
                'string',
                'max:100',
            ],
            'tanggal_titimangsa' => [
                'required',
                'date',
            ],
            'kepala_sekolah_nipy' => [
                'nullable',
                'string',
                'max:255',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'nama_kepala_sekolah_snapshot' => [
                'nullable',
                'string',
                'max:150',
                'required_without:kepala_sekolah_nipy',
            ],
            'jabatan_kepala_sekolah' => [
                'required',
                'string',
                'max:100',
            ],
            'status' => [
                'nullable',
                'boolean',
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
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun Ajaran yang dipilih tidak valid.',
            'jenjang_kelas.required' => 'Jenjang kelas wajib dipilih.',
            'jenjang_kelas.max' => 'Jenjang kelas maksimal 20 karakter.',
            'tingkatan_kelas.required' => 'Cakupan tingkatan kelas wajib diisi.',
            'tingkatan_kelas.max' => 'Tingkatan kelas maksimal 20 karakter.',
            'periode.required' => 'Periode rapor wajib dipilih.',
            'periode.in' => 'Periode rapor harus Mid Semester atau Semester.',
            'periode.unique' => 'Pengaturan titimangsa untuk kombinasi Tahun Ajaran, Jenjang, Tingkatan, dan Periode ini sudah ada.',
            'tempat.required' => 'Kota / tempat terbit titimangsa wajib diisi.',
            'tempat.max' => 'Kota / tempat terbit maksimal 100 karakter.',
            'tanggal_titimangsa.required' => 'Tanggal titimangsa wajib diisi.',
            'tanggal_titimangsa.date' => 'Format tanggal titimangsa tidak valid.',
            'kepala_sekolah_nipy.exists' => 'Kepala Sekolah yang dipilih tidak ditemukan dalam data karyawan.',
            'nama_kepala_sekolah_snapshot.required_without' => 'Pilih Kepala Sekolah dari data karyawan atau isi nama penandatangan secara manual.',
            'nama_kepala_sekolah_snapshot.max' => 'Nama Kepala Sekolah maksimal 150 karakter.',
            'jabatan_kepala_sekolah.required' => 'Jabatan penandatangan wajib diisi.',
            'jabatan_kepala_sekolah.max' => 'Jabatan penandatangan maksimal 100 karakter.',
        ];
    }
}

