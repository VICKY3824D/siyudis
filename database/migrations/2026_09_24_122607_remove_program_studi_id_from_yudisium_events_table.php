<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_events', function (Blueprint $table) {
            $table->dropForeign(['program_studi_id']);
            $table->dropColumn('program_studi_id');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_events', function (Blueprint $table) {
            $table->foreignId('program_studi_id')->nullable()->constrained('program_studi')->nullOnDelete();
        });
    }
};
