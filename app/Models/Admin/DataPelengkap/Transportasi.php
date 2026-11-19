<?php

namespace App\Models\Admin\DataPelengkap;

use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama_transportasi'])]
#[Table('akademik_pelengkap_transportasi')]
class Transportasi extends Model
{
    use HasFactory;
    use HasSqidRouteKey;
}
