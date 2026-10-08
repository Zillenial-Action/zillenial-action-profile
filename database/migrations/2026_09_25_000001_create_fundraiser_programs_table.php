<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan fundraiser per event yang dibuat admin. Setiap kode fundraiser
        // (kode_vouchers) menyalin diskon, kuota, dan masa berlaku dari sini.
        Schema::create('fundraiser_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_event')->unique()->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('nilai_diskon');
            $table->unsignedInteger('nilai_komisi');
            $table->unsignedInteger('kuota_per_kode');
            $table->date('tanggal_berakhir');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundraiser_programs');
    }
};
