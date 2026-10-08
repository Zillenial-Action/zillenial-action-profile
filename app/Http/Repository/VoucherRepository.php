<?php

namespace App\Repositories;

use App\Models\KodeVoucher;

class VoucherRepository
{
    public function findById(int $id)
    {
        return KodeVoucher::lockForUpdate()->find($id);
    }
}