<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class CustomerTransactionClaimService
{
    public function claim(Customer $customer): int
    {
        if (! $customer->hasVerifiedEmail()) {
            return 0;
        }

        return DB::transaction(function () use ($customer): int {
            return Transaksi::query()
                ->whereNull('id_customer')
                ->whereRaw('LOWER(email) = ?', [$customer->email])
                ->update(['id_customer' => $customer->id]);
        });
    }
}
