<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sqids Alphabet & Minimum Length
    |--------------------------------------------------------------------------
    |
    | Jika SQIDS_ALPHABET kosong, App\Support\Sqid akan mengacak alfabet
    | alfanumerik standar (62 karakter) secara deterministik berdasarkan APP_KEY.
    |
    */
    'alphabet' => (string) env('SQIDS_ALPHABET', ''),

    'default_alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',

    'min_length' => max(8, (int) env('SQIDS_MIN_LENGTH', 8)),
];
