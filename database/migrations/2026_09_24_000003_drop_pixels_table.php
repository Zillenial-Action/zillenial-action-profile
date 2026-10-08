<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fitur pixel per event (admin/pixel) tidak pernah dipakai portal; ID Meta/TikTok
// ditulis langsung di view portal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pixels');
    }

    public function down(): void
    {
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('pixel_code')->nullable()->comment('Pixel tracking ID from Meta/TikTok');
            $table->foreignId('id_event')->constrained('events')->onDelete('cascade');
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
        });
    }
};
