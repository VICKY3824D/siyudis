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
        Schema::create('berita_acara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yudisium_event_id')->constrained('yudisium_events')->onDelete('cascade');
            $table->foreignId('program_studi_id')->constrained('program_studi')->onDelete('cascade');
            $table->string('nomor_surat', 100)->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->string('periode', 255)->nullable();
            $table->string('status')->default('diajukan');
            $table->foreignId('diajukan_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('diajukan_at')->nullable();
            $table->foreignId('approved_kaprodi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_kaprodi_at')->nullable();
            $table->foreignId('approved_manit_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_manit_at')->nullable();
            $table->foreignId('approved_kadep_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_kadep_at')->nullable();
            $table->text('catatan_revisi_internal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berita_acara');
    }
};
