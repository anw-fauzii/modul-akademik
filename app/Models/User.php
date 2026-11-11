<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

#[Fillable(['name', 'email','no_induk', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function logoutFromSSOServer()
    {
        $accessToken = session()->get('access_token');
        
        if (!$accessToken) {
            return false;
        }

        try {
            Http::timeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->post(config('auth.host') . '/api/logmeout');

            return true;
        } catch (\Exception $e) {
            Log::error('Gagal mencabut token di Server SSO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Relasi ke profil kepegawaian karyawan.
     */
    public function karyawan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Admin\DataMaster\Karyawan::class, 'nipy', 'no_induk');
    }
}
