<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            $table->foreignId('approved_kaprodi_by')->nullable()->after('catatan_revisi_internal')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_kaprodi_at')->nullable()->after('approved_kaprodi_by');

            $table->foreignId('approved_manit_by')->nullable()->after('approved_kaprodi_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_manit_at')->nullable()->after('approved_manit_by');

            $table->foreignId('approved_kadep_by')->nullable()->after('approved_manit_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_kadep_at')->nullable()->after('approved_kadep_by');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            $table->dropForeign(['approved_kaprodi_by']);
            $table->dropForeign(['approved_manit_by']);
            $table->dropForeign(['approved_kadep_by']);
            $table->dropColumn([
                'approved_kaprodi_by', 'approved_kaprodi_at',
                'approved_manit_by', 'approved_manit_at',
                'approved_kadep_by', 'approved_kadep_at',
            ]);
        });
    }
};
