<?php

namespace App\Models\Admin\DataMaster;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama_tahun_ajaran', 'semester', 'is_active'])]
#[Table('akademik_master_tahun_ajaran')]
class TahunAjaran extends Model
{
    use HasFactory;
}
