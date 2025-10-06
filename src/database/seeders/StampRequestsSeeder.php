<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

class StampRequestsSeeder extends Seeder
{
    public function run(): void
    {
        // 一般ユーザーを1名作成
        $user = User::factory()->create([
            'email' => 'user-seed@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        // 当日の勤怠
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        // 申請：pending / approved / rejected を作成
        StampCorrectionRequest::factory()->pending()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
        ]);

        StampCorrectionRequest::factory()->approved()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
        ]);

        StampCorrectionRequest::factory()->rejected()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
        ]);
    }
}
