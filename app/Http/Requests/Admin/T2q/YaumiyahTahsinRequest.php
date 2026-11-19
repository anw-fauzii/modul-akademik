<?php

namespace App\Http\Requests\Admin\T2q;

class YaumiyahTahsinRequest extends BaseYaumiyahT2qRequest
{
    protected function requiresDaftarJilid(): bool
    {
        return true;
    }
}

