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
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->string('approved_kaprodi_signature_path')->nullable()->after('approved_kaprodi_at');
            $table->string('approved_kaprodi_nama')->nullable()->after('approved_kaprodi_signature_path');
            $table->string('approved_manit_signature_path')->nullable()->after('approved_manit_at');
            $table->string('approved_manit_nama')->nullable()->after('approved_manit_signature_path');
            $table->string('approved_kadep_signature_path')->nullable()->after('approved_kadep_at');
            $table->string('approved_kadep_nama')->nullable()->after('approved_kadep_signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn([
                'approved_kaprodi_signature_path',
                'approved_kaprodi_nama',
                'approved_manit_signature_path',
                'approved_manit_nama',
                'approved_kadep_signature_path',
                'approved_kadep_nama',
            ]);
        });
    }
};
