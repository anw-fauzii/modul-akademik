<?php

namespace App\Http\Requests\Admin\T2q;

class YaumiyahTahfizhRequest extends BaseYaumiyahT2qRequest
{
    protected function requiresDaftarJilid(): bool
    {
        return false;
    }
}
