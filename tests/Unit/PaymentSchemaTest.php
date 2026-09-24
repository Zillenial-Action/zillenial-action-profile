<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaymentSchemaTest extends TestCase
{
    public function test_payment_account_number_is_nullable_in_base_migration(): void
    {
        $migration = file_get_contents(database_path('migrations/2024_10_29_023122_create_payments_table.php'));

        $this->assertStringContainsString("\$table->string('no_rek')->nullable();", $migration);
    }
}
