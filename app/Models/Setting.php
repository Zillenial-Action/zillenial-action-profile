<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan global berbentuk key-value. Akses lewat service (mis. TicketSettings),
 * bukan langsung, agar default dan cache tertangani.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];
}
