<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Volunteer
 *
 * Represents a volunteer participant in an event transaction.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $telepon
 * @property string|null $jenis_kelamin
 * @property string|null $domisili
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Volunteer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Pilihan ukuran baju untuk event yang menyediakan baju (events.ada_baju).
     */
    public const UKURAN_BAJU = ['XXS', 'XS', 'S', 'M', 'L', 'XL'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telepon',
        'jenis_kelamin',
        'domisili',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the transactions associated with the volunteer.
     */
    public function transaksis(): BelongsToMany
    {
        return $this->belongsToMany(Transaksi::class, 'transaksi_volunteers', 'id_volunteer', 'id_transaksi')
                    ->withPivot('ukuran_baju')
                    ->withTimestamps();
    }
}
