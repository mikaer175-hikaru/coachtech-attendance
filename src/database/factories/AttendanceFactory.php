<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        // デフォルトは「勤務外」に寄せる（テストの驚きを減らす）
        $date = Carbon::today()->subDays(rand(0, 29));
        $date = $date->isWeekend() ? $date->previousWeekday() : $date;

        return [
            'user_id'    => User::factory(),
            'work_date'  => $date->toDateString(),
            'start_time' => null,
            'end_time'   => null,
            'status'     => Attendance::STATUS_OFF,
            'note'       => null,
        ];
    }

    /** 出勤中（startあり、endなし） */
    public function working(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        return $this->state(function () use ($d) {
            $start = Carbon::create($d->year, $d->month, $d->day, 9, 0);
            return [
                'work_date'  => $d->toDateString(),
                'start_time' => $start,
                'end_time'   => null,
                'status'     => Attendance::STATUS_WORKING,
            ];
        });
    }

    /** 退勤済（start/endあり） */
    public function ended(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        $start = Carbon::create($d->year, $d->month, $d->day, 9, 0);
        $end   = (clone $start)->addHours(8);
        return $this->state([
            'work_date'  => $d->toDateString(),
            'start_time' => $start,
            'end_time'   => $end,
            'status'     => Attendance::STATUS_ENDED,
        ]);
    }

    /** 勤務外 */
    public function off(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        return $this->state([
            'work_date'  => $d->toDateString(),
            'start_time' => null,
            'end_time'   => null,
            'status'     => Attendance::STATUS_OFF,
        ]);
    }
}
