<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yudisium_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('nama_periode'); // Contoh: "Yudisium Periode IV TA 2025/2026"
            $table->dateTime('tgl_buka');
            $table->dateTime('tgl_tutup');
            $table->date('tgl_yudisium')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yudisium_events');
    }
};
