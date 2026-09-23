<?php

namespace App\Models;

use App\Notifications\VerifyCustomerEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable implements MustVerifyEmailContract
{
    use CanResetPassword, HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_customer');
    }

    /**
     * Kirim mailable verifikasi bermerek Zillenial Action alih-alih notifikasi bawaan Laravel.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyCustomerEmail);
    }
}
