<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_berita_acara_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')
                ->unique()
                ->constrained('pengajuan_yudisium')
                ->cascadeOnDelete();
            $table->decimal('ipk', 3, 2);
            $table->decimal('persen_nilai_d', 5, 2);
            $table->integer('sks_ditempuh');
            $table->decimal('similarity_index', 5, 2);
            $table->integer('skor_bahasa_inggris');
            $table->string('sertifikat_level')->nullable();
            $table->string('status_judul_pa');
            $table->string('sertifikat_kompetensi')->nullable();
            $table->boolean('status_bebas_pelanggaran');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_berita_acara_mahasiswa');
    }
};
