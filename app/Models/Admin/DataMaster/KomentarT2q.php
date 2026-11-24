<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'komentar',
])]
#[Table('akademik_master_t2q_komentar')]
class KomentarT2q extends Model
{
    use HasSqidRouteKey;

    /**
     * Daftar master komentar standar T2Q untuk seeder & fallback offline.
     *
     * @var list<string>
     */
    public const array DEFAULT_COMMENTS = [
        'huruf hijaiyah lepas berharakat fathah kasroh dan dhommah',
        'huruf hijaiyah sambung berharakat fathah kasroh dan dhommah',
        'huruf hijaiyah berharakat tanwin',
        'pengenalan nama huruf hijaiyah',
        'bacaan panjang 2 harakat (mad)',
        'huruf yang disukunkan',
        'pengenalan harakat fathah kasroh dan dhommah panjang',
        "perbedaan huruf 'ain dan hamzah sukun",
        'huruf-huruf yang bertasydid',
        'bacaan lin',
        'mad wajib muttasil dan mad jaiz munfashil',
        'bacaan ghunnah',
        'bacaan ro tebal dan ro tipis',
        'bacaan idghom bigunnah dan idghom bilagunnah',
        'bacaan dengung',
        'iqlab',
        'ikhfa haqiqi',
        'ikhfa syafawi',
        'mad lazim mutsaqol kilmi',
        'bacaan saktah',
        'bacaan imalah',
        'bacaan tahsil',
        'bacaan naql',
        'bacaan isymam',
        'bacaan nun wiqoyah',
        'bacaan yang dibaca pendek ketika washol dan dibaca panjang ketika waqof',
        'bacaan yang dibaca pendek ketika washol dan dibaca sukun ketika waqof',
        'bacaan hati-hati',
        'bacaan waqof dan washol',
        'ayat-ayat sajdah',
        'makhroj',
        'mad',
        'dengung',
        'qolqolah',
        'kelancaran',
        'pengenalan angka arab 1-100',
        'pengenalan nama harakat fathah kasroh dhommah dan tanwin',
        'alif lam qomariyah dan syamsiyah',
        'bacaan yang diwaqofkan',
        'bacaan idzhar',
        'lafadz allah',
        'bacaan qolqolah',
        'bacaan mad lazim mutsaqol kalimi',
        'huruf wawu yang tidak dibaca panjang',
        'fawatihussuwar',
        'pengenalan tanda waqaf dan washal',
        'konsistensi makhraj',
        'konsistensi mad',
        'konsistensi dengung',
        'bacaan tanafus (terputus)',
        'bacaan tartil',
        'idzhar muthlaq',
        'hukum ro',
        'hukum alif lam',
        'hukum mad',
        'hukum nun sukun atau tanwin',
        'hukum mim sukun',
        'macam-macam idghom',
        'hukum lafadz allah',
        'gunnah',
        'konsistensi harakat damah',
        'konsistensi harakat fathah',
        'konsistensi harakat kasrah',
        'konsistensi harakat fathatain',
        'konsistensi harakat kasratain',
        'konsistensi harakat damatain',
        'konsistensi alif lam qamariah dan syamsiah',
        'konsistensi huruf yang disukunkan',
        'konsistensi harakat fathah, kasrah, dan damah',
        'konsistensi huruf yang bertasydid',
        'konsistensi tanafus (terputus)',
        'konsistensi makharijul huruf',
        'konsistensi makharijul huruf sambung',
        'konsistensi bacaan mad',
        'konsistensi bacaan ghunnah',
        'konsistensi waqaf ibtida',
        'tanafus (terputus)',
    ];

    /**
     * Mengurutkan komentar T2Q sesuai urutan pengisian master.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('id'));
    }
}
