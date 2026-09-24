<?php

namespace Tests\Unit;

use App\Models\Payment;
use Tests\TestCase;

class PaymentRelationTest extends TestCase
{
    public function test_payment_transaksis_relation_uses_current_foreign_key(): void
    {
        $relation = (new Payment())->transaksis();

        $this->assertSame('id_payment', $relation->getForeignKeyName());
    }
}
