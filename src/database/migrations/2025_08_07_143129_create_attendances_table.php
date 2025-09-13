<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ユーザーと紐づけ
            $table->date('work_date');                     // 勤務日
            $table->dateTime('start_time')->nullable();    // 出勤時刻
            $table->dateTime('end_time')->nullable();      // 退勤時刻
            $table->dateTime('break_start_time')->nullable(); // 休憩開始
            $table->dateTime('break_end_time')->nullable();   // 休憩終了
            $table->timestamps();                          // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
