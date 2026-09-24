<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Event
 *
 * Represents an event that users can register for and purchase tickets.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $mitra
 * @property string|null $website
 * @property string|null $deskripsi
 * @property array<int, string>|null $benefits
 * @property array<int, array<string, string>>|null $agenda
 * @property bool $status
 * @property \Carbon\Carbon $waktu_mulai
 * @property \Carbon\Carbon $waktu_berakhir
 * @property string|null $nama_tempat
 * @property string|null $alamat
 * @property string|null $direction
 * @property string|null $kota
 * @property int $jumlah_tiket
 * @property int $remaining_tickets
 * @property bool $is_sold_out
 * @property int $harga
 * @property string|null $image
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Event extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'mitra',
        'website',
        'deskripsi',
        'benefits',
        'agenda',
        'status',
        'waktu_mulai',
        'waktu_berakhir',
        'nama_tempat',
        'alamat',
        'direction',
        'kota',
        'jumlah_tiket',
        'harga',
        'image',
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
        'waktu_mulai' => 'datetime',
        'waktu_berakhir' => 'datetime',
        'jumlah_tiket' => 'integer',
        'harga' => 'integer',
        'benefits' => 'array',
        'agenda' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = $event->generateUniqueSlug($event->name);
            }
        });

        static::updating(function (Event $event) {
            // Only regenerate slug if name changed and slug wasn't manually set
            if ($event->isDirty('name') && !$event->isDirty('slug')) {
                $event->slug = $event->generateUniqueSlug($event->name, $event->id);
            }
        });
    }

    /**
     * Generate a unique slug for the event.
     *
     * @param string $name The event name
     * @param int|null $excludeId ID to exclude when checking uniqueness
     * @return string
     */
    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->withTrashed()
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check active status defensively because some imports may use Y/N values.
     */
    public function isActive(): bool
    {
        $status = $this->getRawOriginal('status');

        if ($status === null && array_key_exists('status', $this->attributes)) {
            $status = $this->attributes['status'];
        }

        if (is_string($status)) {
            $normalized = strtolower(trim($status));

            return in_array($normalized, ['1', 'y', 'yes', 'true', 'aktif', 'active'], true);
        }

        return (bool) $status;
    }

    /**
     * Remaining ticket stock. Checkout already decrements this field atomically.
     */
    public function remainingTickets(): int
    {
        return max(0, (int) $this->jumlah_tiket);
    }

    /**
     * Public sold-out state: inactive events and empty stock are not purchasable.
     */
    public function isSoldOut(): bool
    {
        return ! $this->isActive() || $this->remainingTickets() <= 0;
    }

    /**
     * Get the transactions for the event.
     */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_event');
    }

    /**
     * Get the voucher codes for the event.
     */
    public function kodeVouchers(): HasMany
    {
        return $this->hasMany(KodeVoucher::class, 'id_event');
    }

    /**
     * Get the tracking pixels for the event.
     */
    public function pixels(): HasMany
    {
        return $this->hasMany(Pixel::class, 'id_event');
    }
}
