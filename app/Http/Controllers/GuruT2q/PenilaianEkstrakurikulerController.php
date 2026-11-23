<?php

namespace App\Http\Controllers\GuruT2q;

use App\Http\Controllers\GuruMapel\PenilaianEkstrakurikulerController as BasePilihanEkskulController;

class PenilaianEkstrakurikulerController extends BasePilihanEkskulController
{
    protected function allowedRoleMiddleware(): string
    {
        return 'role:super admin|guru t2q';
    }

    protected function routeScope(): string
    {
        return 'guru-t2q';
    }
}

