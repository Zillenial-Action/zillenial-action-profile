<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Payment
 *
 * Represents a payment method available for transactions.
 *
 * @property int $id
 * @property string $name
 * @property string $no_rek
 * @property string|null $image
 * @property bool $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    public const FEE_NONE = 'none';

    public const FEE_PERCENT = 'percent';

    public const FEE_FIXED = 'fixed';

    public const FEE_TYPES = [self::FEE_NONE, self::FEE_PERCENT, self::FEE_FIXED];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'no_rek',
        'type',
        'midtrans_payment_type',
        'midtrans_bank',
        'biaya_layanan_tipe',
        'biaya_layanan_nilai',
        'image',
        'status',
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
        'biaya_layanan_nilai' => 'float',
    ];

    /**
     * Biaya layanan metode ini, dinormalisasi: nilai <= 0 atau tipe tak dikenal dianggap tanpa biaya.
     *
     * @return array{tipe: string, nilai: float}
     */
    public function serviceFeeConfig(): array
    {
        $tipe = in_array($this->biaya_layanan_tipe, self::FEE_TYPES, true) ? $this->biaya_layanan_tipe : self::FEE_NONE;
        $nilai = max(0, (float) $this->biaya_layanan_nilai);

        if ($tipe === self::FEE_NONE || $nilai <= 0) {
            return ['tipe' => self::FEE_NONE, 'nilai' => 0.0];
        }

        return ['tipe' => $tipe, 'nilai' => $nilai];
    }

    /**
     * Label singkat untuk tabel admin, mis. "2,5%" atau "Rp4.000 / tiket".
     */
    public function serviceFeeLabel(): string
    {
        ['tipe' => $tipe, 'nilai' => $nilai] = $this->serviceFeeConfig();

        return match ($tipe) {
            self::FEE_PERCENT => rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',').'%',
            self::FEE_FIXED => 'Rp'.number_format($nilai, 0, ',', '.').' / tiket',
            default => '-',
        };
    }

    /**
     * Get the transactions for the payment method.
     */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_payment');
    }
}
