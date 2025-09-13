<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_correct_requests', function (Blueprint $t) {
            $t->timestamp('approved_at')->nullable()->after('status');
            $t->timestamp('rejected_at')->nullable()->after('approved_at');
            // 一覧のフィルタ用にインデックスがあると快適
            $t->index('status');
            $t->index(['user_id', 'status']);
            $t->index('attendance_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_correct_requests', function (Blueprint $t) {
            $t->dropIndex(['attendance_correct_requests_status_index']);
            $t->dropIndex(['attendance_correct_requests_user_id_status_index']);
            $t->dropIndex(['attendance_correct_requests_attendance_id_index']);
            $t->dropColumn(['approved_at', 'rejected_at']);
        });
    }
};
