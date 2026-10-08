<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kode_vouchers', function (Blueprint $table) {
            $table->foreignId('id_fundraiser_program')
                ->nullable()
                ->after('id_event')
                ->constrained('fundraiser_programs')
                ->nullOnDelete();
            $table->foreignId('id_customer')
                ->nullable()
                ->after('id_fundraiser_program')
                ->constrained('customers')
                ->nullOnDelete();

            // Satu fundraiser hanya punya satu kode per program.
            $table->unique(['id_fundraiser_program', 'id_customer']);
        });
    }

    public function down(): void
    {
        Schema::table('kode_vouchers', function (Blueprint $table) {
            $table->dropUnique(['id_fundraiser_program', 'id_customer']);
            $table->dropConstrainedForeignId('id_customer');
            $table->dropConstrainedForeignId('id_fundraiser_program');
        });
    }
};
