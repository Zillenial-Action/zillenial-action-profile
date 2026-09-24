<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminImportExternalRemovedTest extends TestCase
{
    public function test_import_external_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('voucher.import.external'));
        $this->assertFalse(Route::has('voucher.import.external.store'));
    }
}
