<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transaksis', 'id_customer')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->dropForeign(['id_customer']);
                $table->dropIndex(['id_customer']);
                $table->dropColumn('id_customer');
            });
        }

        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customers');
    }

    public function down(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();

            $table->index('email_verified_at');
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        if (! Schema::hasColumn('transaksis', 'id_customer')) {
            Schema::table('transaksis', function (Blueprint $table) {
                $table->foreignId('id_customer')
                    ->nullable()
                    ->after('id_voucher')
                    ->constrained('customers')
                    ->nullOnDelete();
                $table->index('id_customer');
            });
        }
    }
};
