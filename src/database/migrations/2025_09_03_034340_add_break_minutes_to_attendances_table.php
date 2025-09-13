<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'break_minutes')) {
                $table->integer('break_minutes')->default(0);
            }
            if (!Schema::hasColumn('attendances', 'status')) {
                $table->string('status', 20)->default('ended');
            }
            if (!Schema::hasColumn('attendances', 'note')) {
                // ★ テストが note を入れないので nullable にする
                $table->text('note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('attendances', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('attendances', 'break_minutes')) {
                $table->dropColumn('break_minutes');
            }
        });
    }
};