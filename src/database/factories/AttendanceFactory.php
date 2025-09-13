<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        // 平日9:00-18:00（休憩60分）をベースに少しブレさせる
        $date = Carbon::today()->subDays(rand(0, 29));
        $date = $date->isWeekend() ? $date->previousWeekday() : $date;

        $startHour   = $this->faker->numberBetween(8, 10);   // 8〜10時開始
        $startMinute = [0, 15, 30, 45][$this->faker->numberBetween(0, 3)];
        $start       = Carbon::create($date->year, $date->month, $date->day, $startHour, $startMinute);

        $workHours   = $this->faker->numberBetween(7, 10);   // 実働の幅
        $end         = (clone $start)->addHours($workHours)->addMinutes([0, 15, 30][$this->faker->numberBetween(0, 2)]);

        $breakMin    = $this->faker->randomElement([45, 60, 75, 90]); // 休憩分

        return [
            'user_id'       => User::factory(), // Seeder 側で上書きする想定
            'work_date'     => $date->toDateString(),
            'start_time'    => $start,
            'end_time'      => $end,
            'break_minutes' => $breakMin,
            'status'        => Attendance::STATUS_ENDED, // 退勤済
            'note'          => $this->faker->boolean(15) ? '自動打刻調整' : null,
        ];
    }
}
