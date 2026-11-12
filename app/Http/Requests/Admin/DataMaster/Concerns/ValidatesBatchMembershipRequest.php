<?php

namespace App\Http\Requests\Admin\DataMaster\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

trait ValidatesBatchMembershipRequest
{
    /**
     * Menambahkan aturan validasi identifier anggota untuk input batch (POST array) maupun tunggal (PUT/PATCH).
     *
     * @param  array<string, mixed>  $rules
     * @param  list<string>  $scalarRules
     */
    protected function applyBatchMemberRules(
        array &$rules,
        string $field,
        array $scalarRules,
        bool $isBatchStore
    ): void {
        if ($isBatchStore) {
            $rules[$field] = ['required', 'array', 'min:1'];
            $rules["{$field}.*"] = array_values(array_merge(
                array_slice($scalarRules, 0, -1),
                ['distinct', $scalarRules[array_key_last($scalarRules)]]
            ));

            return;
        }

        $rules[$field] = $scalarRules;
    }

    /**
     * Mengekstrak daftar identifier anggota baik dalam bentuk array maupun skalar.
     *
     * @return list<string>
     */
    protected function extractMemberIdentifierList(string $field): array
    {
        $rawValue = $this->input($field);

        return is_array($rawValue)
            ? array_values(array_map('strval', $rawValue))
            : [(string) $rawValue];
    }

    /**
     * Memvalidasi kesesuaian tahun ajaran anggota kelas dan mendeteksi duplikasi keanggotaan dalam tahun ajaran sama.
     *
     * @param  class-string<Model>  $groupModelClass
     */
    protected function validateAnggotaKelasGroupConflict(
        Validator $validator,
        string $groupField,
        string $groupModelClass,
        object $membershipService,
        string $routeParamName,
        string $entityLabel
    ): void {
        if ($validator->errors()->hasAny([$groupField, 'anggota_kelas_id', 'anggota_kelas_id.*'])) {
            return;
        }

        $tahunAjaranId = $groupModelClass::query()
            ->whereKey($this->integer($groupField))
            ->value('akademik_master_tahun_ajaran_id');

        if ($tahunAjaranId === null) {
            return;
        }

        $anggotaKelasIds = array_values(array_unique(array_map(
            'intval',
            $this->extractMemberIdentifierList('anggota_kelas_id')
        )));

        $this->validateAnggotaKelasConflictsInTahunAjaran(
            $validator,
            $anggotaKelasIds,
            (int) $tahunAjaranId,
            $membershipService,
            $routeParamName,
            $entityLabel
        );
    }

    /**
     * Memvalidasi kecocokan tahun ajaran dan konflik siswa untuk satu target tahun ajaran.
     *
     * @param  list<int>  $anggotaKelasIds
     */
    protected function validateAnggotaKelasConflictsInTahunAjaran(
        Validator $validator,
        array $anggotaKelasIds,
        int $tahunAjaranId,
        object $membershipService,
        string $routeParamName,
        string $entityLabel
    ): bool {
        $mismatchedIds = $membershipService->findMismatchedTahunAjaranAnggotaKelas(
            $anggotaKelasIds,
            $tahunAjaranId
        );

        if ($mismatchedIds !== []) {
            $validator->errors()->add(
                'anggota_kelas_id',
                "Anggota kelas yang dipilih tidak berada pada tahun ajaran dan semester yang sama dengan {$entityLabel} tujuan."
            );

            return false;
        }

        $currentRecord = $this->route($routeParamName);
        $ignoreId = $currentRecord instanceof Model
            ? (int) $currentRecord->getKey()
            : null;

        $conflicts = $membershipService->findConflictingAnggotaKelasInTahunAjaran(
            $anggotaKelasIds,
            $tahunAjaranId,
            $ignoreId
        );

        if ($conflicts !== []) {
            $validator->errors()->add(
                'anggota_kelas_id',
                'Siswa dengan NIS '.implode(', ', $conflicts)." sudah terdaftar di {$entityLabel} pada tahun ajaran dan semester yang dipilih."
            );

            return false;
        }

        return true;
    }
}
