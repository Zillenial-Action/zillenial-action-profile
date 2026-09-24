<?php

namespace Tests\Feature;

use App\Models\KodeVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KodeVoucherFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_state_sets_kuota_one_and_flag(): void
    {
        $voucher = KodeVoucher::factory()->external()->create();

        $this->assertTrue($voucher->is_external);
        $this->assertSame(1, $voucher->kuota);
    }
}
