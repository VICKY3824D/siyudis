<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_yudisium', function (Blueprint $table) {
            $table->id();
            // Unique: mahasiswa hanya boleh submit pengajuan yudisium 1x sepanjang masa studi.
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('yudisium_event_id')->constrained('yudisium_events')->cascadeOnDelete();

            // Alur status: submitted -> staf_akademik_review -> waiting_student_confirmation
            // -> student_revision_requested (loop balik ke waiting_student_confirmation)
            // -> staf_akademik_checklist -> kaprodi_review
            // -> manit_kadep_review -> completed
            // (kaprodi/manit/kadep bisa reject -> reset_to_akademik)
            $table->string('status')->default('submitted');

            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_yudisium');
    }
};
