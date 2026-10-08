<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Pengaturan fundraiser untuk satu event: diskon yang didapat pembeli dan
 * komisi yang didapat fundraiser per tiket dari kode miliknya.
 *
 * @property int $id
 * @property int $id_event
 * @property int $nilai_diskon
 * @property int $nilai_komisi
 * @property int $kuota_per_kode
 * @property \Carbon\Carbon $tanggal_berakhir
 * @property bool $status
 */
class FundraiserProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_event',
        'nilai_diskon',
        'nilai_komisi',
        'kuota_per_kode',
        'tanggal_berakhir',
        'status',
    ];

    protected $casts = [
        'nilai_diskon' => 'integer',
        'nilai_komisi' => 'integer',
        'kuota_per_kode' => 'integer',
        'tanggal_berakhir' => 'date',
        'status' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event');
    }

    public function kodeVouchers(): HasMany
    {
        return $this->hasMany(KodeVoucher::class, 'id_fundraiser_program');
    }

    /**
     * Program yang masih bisa dipakai untuk membuat kode baru.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', true)
            ->where('tanggal_berakhir', '>=', now()->startOfDay())
            ->whereHas('event', fn (Builder $event) => $event->where('status', true));
    }

    public function isOpen(): bool
    {
        return $this->status
            && $this->tanggal_berakhir->greaterThanOrEqualTo(now()->startOfDay())
            && $this->event?->isActive();
    }

    /**
     * Samakan setting semua kode fundraiser dengan program, dipanggil setiap
     * admin mengubah program. Kuota yang sudah terpakai tidak disentuh.
     */
    public function syncKodeVouchers(): void
    {
        $this->kodeVouchers()->update([
            'nilai_diskon' => $this->nilai_diskon,
            'kuota' => $this->kuota_per_kode,
            'tanggal_kadaluarsa' => $this->tanggal_berakhir->toDateString(),
            'status' => $this->status,
        ]);
    }

    /**
     * Kode milik customer untuk program ini. Dibuat sekali, panggilan berikutnya
     * mengembalikan kode yang sama.
     */
    public function kodeForCustomer(Customer $customer): KodeVoucher
    {
        $existing = $this->kodeVouchers()->where('id_customer', $customer->id)->first();
        if ($existing) {
            return $existing;
        }

        $kode = self::generateKode($customer->name);

        try {
            return $this->kodeVouchers()->create([
                'id_event' => $this->id_event,
                'id_customer' => $customer->id,
                'name_voucher' => 'Fundraiser '.$kode,
                'kode' => $kode,
                'nilai_diskon' => $this->nilai_diskon,
                'kuota' => $this->kuota_per_kode,
                'tanggal_kadaluarsa' => $this->tanggal_berakhir->toDateString(),
                'status' => $this->status,
                'is_external' => false,
            ]);
        } catch (QueryException $e) {
            // Dua request bersamaan (misal tombol diklik dua kali): unique index
            // menolak kode kedua, pakai kode yang sudah tersimpan.
            return $this->kodeVouchers()->where('id_customer', $customer->id)->first() ?? throw $e;
        }
    }

    /**
     * Kode mudah dibaca: nama depan (maks 8 huruf) + 4 karakter acak, tanpa
     * karakter yang mirip seperti 0/O dan 1/I.
     */
    public static function generateKode(string $name): string
    {
        $prefix = Str::upper(Str::limit(preg_replace('/[^A-Za-z]/', '', Str::ascii(Str::before(trim($name), ' '))), 8, ''));
        if ($prefix === '') {
            $prefix = 'FR';
        }

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $kode = $prefix.'-'.$suffix;
        } while (KodeVoucher::withTrashed()->where('kode', $kode)->exists());

        return $kode;
    }
}
