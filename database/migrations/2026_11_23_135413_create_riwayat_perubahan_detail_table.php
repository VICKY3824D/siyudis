<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_perubahan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riwayat_perubahan_id')->constrained('riwayat_perubahan')->cascadeOnDelete();
            $table->string('field');
            $table->text('nilai_lama')->nullable();
            $table->text('nilai_baru')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_perubahan_detail');
    }
};
