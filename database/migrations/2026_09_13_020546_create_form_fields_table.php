<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('field_name');
            $table->text('field_desc')->nullable();
            $table->enum('data_type', ['freetext', 'pdf', 'link', 'image', 'number', 'single_option']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->integer('order_position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
