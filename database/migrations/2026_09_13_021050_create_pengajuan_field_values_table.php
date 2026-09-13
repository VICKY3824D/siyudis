<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id');
//                ->constrained('pengajuan_yudisium')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_field_values');
    }
};
