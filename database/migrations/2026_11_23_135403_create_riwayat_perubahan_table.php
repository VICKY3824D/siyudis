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
        Schema::create('riwayat_perubahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_name');
            $table->string('modul');
            $table->string('jenis');
            $table->foreignId('pengajuan_id')->nullable()->constrained('pengajuan_yudisium')->cascadeOnDelete();
            $table->foreignId('berita_acara_id')->nullable()->constrained('berita_acara')->cascadeOnDelete();
            $table->text('ringkasan');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['modul', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_perubahan');
    }
};
