<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_field_values', function (Blueprint $table) {
            $table->foreign('pengajuan_id')
                ->references('id')->on('pengajuan_yudisium')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_field_values', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_id']);
        });
    }
};
