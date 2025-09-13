<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_admin', false)->get(); // 一般ユーザーのみ対象

        // 直近30日ぶんの平日データを作成
        $days = 30;

        foreach ($users as $user) {
            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::today()->subDays($i);
                if ($date->isWeekend()) {
                    continue; // 週末はスキップ（勤務外）
                }

                // 欠勤/半休の揺らぎ（たまに欠ける日も作る）
                if (fake()->boolean(10)) {
                    continue;
                }

                Attendance::factory()->create([
                    'user_id'   => $user->id,
                    'work_date' => $date->toDateString(),
                    // start_time/end_time/break_minutes は Factory のロジックを利用
                ]);
            }
        }
    }
}
