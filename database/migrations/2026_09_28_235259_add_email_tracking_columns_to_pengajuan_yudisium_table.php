<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan_yudisium', 'email_status')) {
                $table->string('email_status', 20)->nullable()->after('catatan_revisi_internal');
            }

            if (!Schema::hasColumn('pengajuan_yudisium', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('email_status');
            }

            if (!Schema::hasColumn('pengajuan_yudisium', 'email_error')) {
                $table->text('email_error')->nullable()->after('email_sent_at');
            }

            if (!Schema::hasColumn('pengajuan_yudisium', 'email_attempts')) {
                $table->unsignedSmallInteger('email_attempts')->default(0)->after('email_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_yudisium', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_yudisium', 'email_status')) {
                $table->dropColumn('email_status');
            }

            if (Schema::hasColumn('pengajuan_yudisium', 'email_sent_at')) {
                $table->dropColumn('email_sent_at');
            }

            if (Schema::hasColumn('pengajuan_yudisium', 'email_error')) {
                $table->dropColumn('email_error');
            }

            if (Schema::hasColumn('pengajuan_yudisium', 'email_attempts')) {
                $table->dropColumn('email_attempts');
            }
        });
    }
};
