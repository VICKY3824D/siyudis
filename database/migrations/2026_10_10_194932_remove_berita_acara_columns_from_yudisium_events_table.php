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
        Schema::table('yudisium_events', function (Blueprint $table) {
            if (Schema::hasColumn('yudisium_events', 'nomor_surat')) {
                $table->dropColumn('nomor_surat');
            }

            if (Schema::hasColumn('yudisium_events', 'tanggal_surat')) {
                $table->dropColumn('tanggal_surat');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yudisium_events', function (Blueprint $table) {
            if (!Schema::hasColumn('yudisium_events', 'nomor_surat')) {
                $table->string('nomor_surat', 100)->nullable()->after('periode');
            }

            if (!Schema::hasColumn('yudisium_events', 'tanggal_surat')) {
                $table->date('tanggal_surat')->nullable()->after('nomor_surat');
            }
        });
    }
};
