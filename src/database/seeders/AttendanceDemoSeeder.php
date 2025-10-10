<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonPeriod;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $targetUserId = 3;          // 確認したいユーザーID
        $targetMonth  = '2025-10';  // 確認したい月（YYYY-MM）

        $user  = User::find($targetUserId) ?? User::factory()->create(['id' => $targetUserId]);
        $start = Carbon::parse($targetMonth . '-01')->startOfDay();
        $end   = (clone $start)->endOfMonth();

        // 既存の当月データは削除（重複防止）
        Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->delete();

        foreach (CarbonPeriod::create($start, '1 day', $end) as $d) {
            if ($d->isWeekend()) continue;
            if (random_int(1, 100) <= 10) continue; // 欠勤 10%

            \Database\Factories\AttendanceFactory::new()
                ->forUser($user)
                ->onDate($d->toDateString())
                ->create();
        }
    }
}
