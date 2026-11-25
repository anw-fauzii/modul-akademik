<?php

namespace App\Http\Requests\Admin\TujuanPembelajaran;

use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TujuanPembelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawTa = $this->input('akademik_master_tahun_ajaran_id');
        $rawMapel = $this->input('akademik_pelajaran_mata_pelajaran_id');
        $rawTingkatan = $this->input('tingkatan_kelas');
        $rawFase = $this->input('fase');
        $rawElemen = $this->input('elemen');
        $rawKode = $this->input('kode_tp');
        $rawTp = $this->input('tujuan_pembelajaran');
        $rawUrutan = $this->input('urutan');

        $tingkatan = $this->normalizeCollapsedText($rawTingkatan);
        $fase = $this->normalizeFaseValue($rawFase);
        $mapelId = is_string($rawMapel) ? trim($rawMapel) : $rawMapel;

        if ($fase === null && is_numeric($mapelId) && is_string($tingkatan) && $tingkatan !== '') {
            $unit = (string) (MataPelajaran::query()
                ->select(['id', 'unit'])
                ->find((int) $mapelId)?->unit ?? 'SD');
            $fase = TujuanPembelajaran::resolveDefaultFase($unit, $tingkatan);
        }

        $urutan = is_string($rawUrutan) ? trim($rawUrutan) : $rawUrutan;
        if ($urutan === null || $urutan === '') {
            $urutan = 1;
        }

        $normalized = [
            'akademik_master_tahun_ajaran_id' => is_string($rawTa) ? trim($rawTa) : $rawTa,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelId,
            'tingkatan_kelas' => $tingkatan,
            'fase' => $fase,
            'elemen' => $this->normalizeNullableCollapsedText($rawElemen),
            'kode_tp' => is_string($rawKode) ? strtoupper(trim($rawKode)) : $rawKode,
            'tujuan_pembelajaran' => is_string($rawTp) ? trim($rawTp) : $rawTp,
            'urutan' => $urutan,
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
        $tp = $this->route('tujuan_pembelajaran');
        $tpId = $tp instanceof TujuanPembelajaran ? $tp->getKey() : $tp;

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
            'akademik_pelajaran_mata_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id'),
            ],
            'tingkatan_kelas' => [
                'required',
                'string',
                'max:20',
            ],
            'fase' => [
                'nullable',
                'string',
                'max:20',
                Rule::in(array_keys(TujuanPembelajaran::FASE_OPTIONS)),
            ],
            'elemen' => [
                'nullable',
                'string',
                'max:150',
            ],
            'kode_tp' => [
                'required',
                'string',
                'max:30',
                Rule::unique('akademik_pelajaran_tujuan_pembelajaran', 'kode_tp')
                    ->where(fn ($query) => $query
                        ->where('akademik_master_tahun_ajaran_id', $this->input('akademik_master_tahun_ajaran_id'))
                        ->where('akademik_pelajaran_mata_pelajaran_id', $this->input('akademik_pelajaran_mata_pelajaran_id'))
                        ->where('tingkatan_kelas', $this->input('tingkatan_kelas')))
                    ->ignore($tpId),
            ],
            'tujuan_pembelajaran' => [
                'required',
                'string',
                'max:2000',
            ],
            'urutan' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran / Semester',
            'akademik_pelajaran_mata_pelajaran_id' => 'Mata Pelajaran',
            'tingkatan_kelas' => 'Tingkatan Kelas',
            'fase' => 'Fase',
            'elemen' => 'Elemen',
            'kode_tp' => 'Kode TP',
            'tujuan_pembelajaran' => 'Tujuan Pembelajaran',
            'urutan' => 'Urutan',
            'status' => 'Status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran / semester wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran / semester tidak ditemukan.',
            'akademik_pelajaran_mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
            'akademik_pelajaran_mata_pelajaran_id.exists' => 'Mata pelajaran tidak ditemukan.',
            'tingkatan_kelas.required' => 'Tingkatan kelas wajib diisi atau dipilih.',
            'tingkatan_kelas.max' => 'Tingkatan kelas maksimal 20 karakter.',
            'fase.in' => 'Fase harus bernilai Fondasi, A, B, atau C.',
            'elemen.max' => 'Elemen maksimal 150 karakter.',
            'kode_tp.required' => 'Kode TP wajib diisi.',
            'kode_tp.max' => 'Kode TP maksimal 30 karakter.',
            'kode_tp.unique' => 'Kode TP tersebut sudah terdaftar pada semester, mata pelajaran, dan tingkatan yang sama.',
            'tujuan_pembelajaran.required' => 'Rumusan Tujuan Pembelajaran wajib diisi.',
            'tujuan_pembelajaran.max' => 'Tujuan Pembelajaran maksimal 2000 karakter.',
            'urutan.required' => 'Urutan tampil wajib diisi.',
            'urutan.integer' => 'Urutan tampil harus berupa angka.',
            'urutan.min' => 'Urutan tampil minimal 1.',
            'status.required' => 'Status wajib dipilih.',
            'status.boolean' => 'Status harus bernilai aktif atau nonaktif.',
        ];
    }

    private function normalizeCollapsedText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return (string) preg_replace('/\s+/', ' ', trim($value));
    }

    private function normalizeNullableCollapsedText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $collapsed = (string) preg_replace('/\s+/', ' ', trim($value));

        return $collapsed === '' ? null : $collapsed;
    }

    private function normalizeFaseValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (strcasecmp($trimmed, 'Fondasi') === 0) {
            return 'Fondasi';
        }

        return strtoupper($trimmed);
    }
}
