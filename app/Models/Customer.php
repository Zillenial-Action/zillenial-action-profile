<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun pembeli di portal Sostrip. Terpisah dari User (admin): login lewat
 * Bearer token Sanctum, bukan session web, jadi tidak menyentuh login admin.
 */
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Transaksi yang dibuat saat customer login, ditambah transaksi dengan email
     * yang sama bila kepemilikan email sudah terbukti. Email yang belum terbukti
     * tidak boleh membuka transaksi orang lain hanya karena alamatnya sama.
     */
    public function transaksiQuery(): Builder
    {
        return Transaksi::query()->where(function (Builder $query) {
            $query->where('id_customer', $this->id);

            if ($this->hasVerifiedEmail()) {
                $query->orWhere('email', $this->email);
            }
        });
    }
}
