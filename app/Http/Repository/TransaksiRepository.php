<?php

namespace App\Repositories;

use App\Models\Transaksi;


class TransaksiRepository
{
    public function create(array $data)
    {
        return Transaksi::create($data);
    }
}
