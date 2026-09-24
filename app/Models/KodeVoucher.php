<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class KodeVoucher
 *
 * Represents a voucher code that can be applied to transactions for discounts.
 *
 * @property int $id
 * @property int $event_id
 * @property string $name_voucher
 * @property string $kode
 * @property int $nilai_diskon
 * @property int $kuota
 * @property int $digunakan
 * @property \Carbon\Carbon $tanggal_kadaluarsa
 * @property bool $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class KodeVoucher extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kode_vouchers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_event',
        'name_voucher',
        'kode',
        'nilai_diskon',
        'kuota',
        'tanggal_kadaluarsa',
        'status',
        'is_external',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'tanggal_kadaluarsa' => 'date',
        'nilai_diskon' => 'integer',
        'kuota' => 'integer',
        'digunakan' => 'integer',
        'is_external' => 'boolean',
    ];

    /**
     * Get the event that owns the voucher.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event');
    }

    /**
     * Get the transactions that used this voucher.
     */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_voucher');
    }
}
