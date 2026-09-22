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
            $table->string('nama_event')->nullable()->after('program_studi_id');
            $table->string('periode')->nullable()->after('nama_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yudisium_events', function (Blueprint $table) {
            $table->dropColumn(['nama_event', 'periode']);
        });
    }
};
