<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropForeign(['diajukan_by']);
            $table->foreignId('diajukan_by')->nullable()->change();
            $table->foreign('diajukan_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropForeign(['diajukan_by']);
            $table->foreignId('diajukan_by')->nullable(false)->change();
            $table->foreign('diajukan_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
