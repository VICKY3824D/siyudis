<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan_yudisium', 'checked_akademik_by')) {
                $table->foreignId('checked_akademik_by')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('pengajuan_yudisium', 'catatan_koreksi_mahasiswa')) {
                $table->text('catatan_koreksi_mahasiswa')->nullable()->after('checked_akademik_by');
            }

            if (!Schema::hasColumn('pengajuan_yudisium', 'catatan_revisi_internal')) {
                $table->text('catatan_revisi_internal')->nullable()->after('catatan_koreksi_mahasiswa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_yudisium', 'checked_akademik_by')) {
                $table->dropForeign(['checked_akademik_by']);
                $table->dropColumn('checked_akademik_by');
            }

            if (Schema::hasColumn('pengajuan_yudisium', 'catatan_koreksi_mahasiswa')) {
                $table->dropColumn('catatan_koreksi_mahasiswa');
            }

            if (Schema::hasColumn('pengajuan_yudisium', 'catatan_revisi_internal')) {
                $table->dropColumn('catatan_revisi_internal');
            }
        });
    }
};
