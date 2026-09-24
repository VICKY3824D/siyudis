<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_field_values', function (Blueprint $table) {
            $table->json('value_history')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_field_values', function (Blueprint $table) {
            $table->dropColumn('value_history');
        });
    }
};