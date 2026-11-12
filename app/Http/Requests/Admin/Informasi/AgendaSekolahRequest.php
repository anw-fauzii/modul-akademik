<?php

namespace App\Http\Requests\Admin\Informasi;

use App\Models\Admin\Informasi\AgendaSekolah;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaSekolahRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalkan input teks dan format jam sebelum validasi dijalankan.
     */
    protected function prepareForValidation(): void
    {
        $rawJam = trim((string) $this->input('jam', ''));
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $rawJam, $matches) === 1) {
            $rawJam = $matches[1];
        }

        $this->merge([
            'nama_agenda' => trim((string) $this->input('nama_agenda', '')),
            'jam' => $rawJam,
            'unit' => trim((string) $this->input('unit', '')),
            'jenis' => AgendaSekolah::JENIS_SISWA,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $agendaSekolah = $this->route('agenda_sekolah');

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'nama_agenda' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_informasi_agenda_sekolah', 'nama_agenda')
                    ->where(fn ($query) => $query
                        ->where('akademik_master_tahun_ajaran_id', $this->input('akademik_master_tahun_ajaran_id'))
                        ->where('jenis', AgendaSekolah::JENIS_SISWA)
                        ->where('tanggal', $this->input('tanggal'))
                        ->where('jam', $this->input('jam'))
                        ->where('unit', $this->input('unit'))
                    )
                    ->ignore($agendaSekolah),
            ],
            'tanggal' => [
                'required',
                'date',
            ],
            'jam' => [
                'required',
                'date_format:H:i',
            ],
            'unit' => [
                'required',
                'string',
                'max:100',
                Rule::in(AgendaSekolah::UNIT_OPTIONS),
            ],
            'jenis' => [
                'required',
                'string',
                Rule::in(AgendaSekolah::JENIS_OPTIONS),
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
            'nama_agenda.required' => 'Nama Agenda wajib diisi.',
            'nama_agenda.max' => 'Nama Agenda maksimal 255 karakter.',
            'nama_agenda.unique' => 'Agenda dengan nama, tanggal, jam, dan unit yang sama sudah terdaftar pada tahun ajaran ini.',
            'tanggal.required' => 'Tanggal agenda wajib diisi.',
            'tanggal.date' => 'Format tanggal agenda tidak valid.',
            'jam.required' => 'Jam agenda wajib diisi.',
            'jam.date_format' => 'Format jam harus HH:MM (contoh: 08:00).',
            'unit.required' => 'Unit wajib dipilih.',
            'unit.max' => 'Unit maksimal 100 karakter.',
            'unit.in' => 'Unit yang dipilih tidak valid.',
        ];
    }
}
