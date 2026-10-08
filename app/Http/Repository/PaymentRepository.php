<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    public function findById(int $id)
    {
        return Payment::lockForUpdate()->find($id);
    }
}