<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class Transaksi
 *
 * Represents a transaction/order for event tickets.
 *
 * @property int $id
 * @property int $event_id
 * @property string $invoice
 * @property int $jumlah_tiket
 * @property int $total_pembayaran
 * @property string $name
 * @property string $email
 * @property string $telepon
 * @property string $status_pembayaran
 * @property \Carbon\Carbon $tanggal_register
 * @property \Carbon\Carbon|null $tanggal_pembayaran
 * @property int|null $payment_id
 * @property int|null $voucher_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Transaksi extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaksis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_event',
        'invoice',
        'jumlah_tiket',
        'total_pembayaran',
        'name',
        'email',
        'telepon',
        'status_pembayaran',
        'tanggal_register',
        'tanggal_pembayaran',
        'id_payment',
        'id_voucher',
        'public_token',
        'snap_token',
        'payment_instructions',
        'pengunjung_data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
        'public_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_register' => 'datetime',
        'tanggal_pembayaran' => 'datetime',
        'jumlah_tiket' => 'integer',
        'total_pembayaran' => 'integer',
        'status_pembayaran' => 'string',
        'payment_instructions' => 'array',
        'pengunjung_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi): void {
            if (empty($transaksi->public_token)) {
                $transaksi->public_token = self::generatePublicToken();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'invoice';
    }

    /**
     * Check whether the public invoice page should still be accessible.
     */
    public function canAccessInvoice(): bool
    {
        if ($this->status_pembayaran === 'Success') {
            return false;
        }

        if (! $this->tanggal_register) {
            return false;
        }

        return Carbon::parse($this->tanggal_register)->greaterThanOrEqualTo(now()->subDay());
    }

    public static function generatePublicToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::withTrashed()->where('public_token', $token)->exists());

        return $token;
    }

    public function hasPublicToken(string $token): bool
    {
        if (empty($this->public_token) || $token === '') {
            return false;
        }

        return hash_equals($this->public_token, $token);
    }

    /**
     * Get the event that the transaction belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event');
    }

    /**
     * Get the payment method used for the transaction.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'id_payment');
    }

    /**
     * Get the voucher applied to the transaction.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(KodeVoucher::class, 'id_voucher');
    }

    /**
     * Get the volunteers associated with the transaction.
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(Volunteer::class, 'transaksi_volunteers', 'id_transaksi', 'id_volunteer')
            ->withTimestamps();
    }
}
