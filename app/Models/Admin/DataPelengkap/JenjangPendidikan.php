<?php

namespace App\Models\Admin\DataPelengkap;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama_jenjang_pendidikan'])]
#[Table('akademik_pelengkap_jenjang_pendidikan')]
class JenjangPendidikan extends Model
{
    use HasFactory;
}
