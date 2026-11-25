<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('berita_acara_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_acara_id')->constrained('berita_acara')->cascadeOnDelete();
            $table->unsignedInteger('cycle')->default(1);
            $table->string('role', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_nama')->nullable();
            $table->string('status', 30);
            $table->string('signature_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->unique(['berita_acara_id', 'cycle', 'role']);
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->unsignedInteger('current_cycle')->default(1)->after('status');
        });

        $now = now();
        $userNames = DB::table('users')->pluck('nama', 'id');

        DB::table('berita_acara')->orderBy('id')->chunkById(100, function ($rows) use ($now, $userNames) {
            foreach ($rows as $row) {
                foreach (['kaprodi', 'manit', 'kadep'] as $role) {
                    $actedAt = $row->{"approved_{$role}_at"} ?? null;
                    if ($actedAt === null) {
                        continue;
                    }

                    $userId = $row->{"approved_{$role}_by"} ?? null;
                    $nama = $row->{"approved_{$role}_nama"} ?? ($userId ? ($userNames[$userId] ?? null) : null);

                    DB::table('berita_acara_approvals')->insert([
                        'berita_acara_id' => $row->id,
                        'cycle' => 1,
                        'role' => $role,
                        'user_id' => $userId,
                        'approver_nama' => $nama,
                        'status' => 'approved',
                        'signature_path' => $row->{"approved_{$role}_signature_path"} ?? null,
                        'catatan' => null,
                        'acted_at' => $actedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropForeign(['approved_kaprodi_by']);
            $table->dropForeign(['approved_manit_by']);
            $table->dropForeign(['approved_kadep_by']);
            $table->dropColumn([
                'approved_kaprodi_by',
                'approved_kaprodi_at',
                'approved_kaprodi_signature_path',
                'approved_kaprodi_nama',
                'approved_manit_by',
                'approved_manit_at',
                'approved_manit_signature_path',
                'approved_manit_nama',
                'approved_kadep_by',
                'approved_kadep_at',
                'approved_kadep_signature_path',
                'approved_kadep_nama',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->foreignId('approved_kaprodi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_kaprodi_at')->nullable();
            $table->string('approved_kaprodi_signature_path')->nullable();
            $table->string('approved_kaprodi_nama')->nullable();
            $table->foreignId('approved_manit_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_manit_at')->nullable();
            $table->string('approved_manit_signature_path')->nullable();
            $table->string('approved_manit_nama')->nullable();
            $table->foreignId('approved_kadep_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_kadep_at')->nullable();
            $table->string('approved_kadep_signature_path')->nullable();
            $table->string('approved_kadep_nama')->nullable();
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn('current_cycle');
        });

        Schema::dropIfExists('berita_acara_approvals');
    }
};
