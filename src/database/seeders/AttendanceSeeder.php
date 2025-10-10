<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // ===== 設定（必要なら変更）=====
        $mode        = 'last30';       // 'last30' か 'month'
        $targetMonth = '2025-10';      // $mode='month' のときに使う YYYY-MM
        $absenceRate = 10;             // 欠勤率（%）
        // =============================

        // 対象ユーザー（一般ユーザーのみ）
        $users = User::where('is_admin', false)->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        // 期間を決める
        if ($mode === 'month') {
            $start = Carbon::parse($targetMonth . '-01')->startOfDay();
            $end   = (clone $start)->endOfMonth();
        } else { // last30 (直近30日)
            $end   = Carbon::today()->endOfDay();
            $start = (clone $end)->subDays(29)->startOfDay();
        }

        // 重複防止：対象期間の勤怠を事前削除（ユーザー単位）
        foreach ($users as $u) {
            Attendance::where('user_id', $u->id)
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->delete();
        }

        // 平日のみ生成（欠勤を確率で混ぜる）
        $period = CarbonPeriod::create($start, '1 day', $end);

        foreach ($users as $user) {
            foreach ($period as $day) {
                if ($day->isWeekend()) {
                    continue; // 週末はスキップ
                }
                if (random_int(1, 100) <= $absenceRate) {
                    continue; // 欠勤
                }

                // → AttendanceFactory の afterCreating で BreakTime を自動作成
                Attendance::factory()
                    ->forUser($user)
                    ->onDate($day->toDateString())
                    ->create();
            }
        }
    }
}
