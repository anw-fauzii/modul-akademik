<?php

namespace App\Http\Requests\Admin\Presensi;

use App\Services\Admin\Presensi\FingerspotSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Throwable;

class FingerspotSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi input tanggal kosong menjadi tanggal hari ini (Asia/Jakarta).
     */
    protected function prepareForValidation(): void
    {
        $today = Carbon::now(FingerspotSyncService::TIMEZONE)->toDateString();
        $rawStart = trim((string) ($this->input('start_date') ?? ''));
        $rawEnd = trim((string) ($this->input('end_date') ?? ''));

        $start = $rawStart !== '' ? $rawStart : $today;
        $end = $rawEnd !== '' ? $rawEnd : $start;

        $this->merge([
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Validasi tambahan untuk membatasi rentang tanggal sinkronisasi (maks. 31 hari).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    $start = Carbon::parse((string) $this->input('start_date'), FingerspotSyncService::TIMEZONE)->startOfDay();
                    $end = Carbon::parse((string) $this->input('end_date'), FingerspotSyncService::TIMEZONE)->startOfDay();
                    $days = (int) $start->diffInDays($end) + 1;

                    if ($days > FingerspotSyncService::MAX_SYNC_RANGE_DAYS) {
                        $validator->errors()->add(
                            'end_date',
                            'Rentang sinkronisasi Fingerspot maksimal '.FingerspotSyncService::MAX_SYNC_RANGE_DAYS.' hari per proses.'
                        );
                    }
                } catch (Throwable) {
                    $validator->errors()->add('start_date', 'Format tanggal sinkronisasi tidak valid.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai sinkronisasi wajib diisi.',
            'start_date.date' => 'Format tanggal mulai tidak valid.',
            'end_date.required' => 'Tanggal akhir sinkronisasi wajib diisi.',
            'end_date.date' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
        ];
    }
}

