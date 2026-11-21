<?php

namespace App\Http\Controllers\GuruMapel;

use App\Http\Controllers\WaliKelas\PenilaianTpController as WaliKelasPenilaianTpController;

class PenilaianTpController extends WaliKelasPenilaianTpController
{
    protected function allowedRoleMiddleware(): string
    {
        return 'role:super admin|guru mapel sd';
    }

    protected function routeScope(): string
    {
        return 'guru-mapel';
    }
}
