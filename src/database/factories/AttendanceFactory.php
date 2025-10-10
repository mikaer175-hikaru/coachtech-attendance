<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /** デフォルト：退勤済（一覧確認向け）*/
    public function definition(): array
    {
        // 当月の平日からランダム
        $date = Carbon::instance(
            $this->faker->dateTimeBetween('first day of this month', 'last day of this month')
        )->startOfDay();
        if ($date->isWeekend()) {
            $date = $date->nextWeekday();
        }

        $start = (clone $date)->setTime(9, 0)->addMinutes($this->faker->numberBetween(-30, 60));
        $end   = (clone $start)->addMinutes($this->faker->numberBetween(8 * 60 + 30, 9 * 60 + 30));

        return [
            'user_id'    => User::factory(),
            'work_date'  => $date->toDateString(),
            'start_time' => $start,
            'end_time'   => $end,
            'status'     => defined(Attendance::class.'::STATUS_ENDED') ? Attendance::STATUS_ENDED : 'ended',
            'note'       => null,
        ];
    }

    /** 勤務外 */
    public function off(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();

        return $this->state([
            'work_date'  => $d->toDateString(),
            'start_time' => null,
            'end_time'   => null,
            'status'     => defined(Attendance::class.'::STATUS_OFF') ? Attendance::STATUS_OFF : 'off',
        ]);
    }

    /** 出勤中（startあり・endなし）*/
    public function working(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        $start = Carbon::create($d->year, $d->month, $d->day, 9, 0);

        return $this->state([
            'work_date'  => $d->toDateString(),
            'start_time' => $start,
            'end_time'   => null,
            'status'     => defined(Attendance::class.'::STATUS_WORKING') ? Attendance::STATUS_WORKING : 'working',
        ]);
    }

    /** 退勤済（start/endあり）*/
    public function ended(Carbon|string|null $date = null): self
    {
        $d = $date ? Carbon::parse($date) : Carbon::today();
        $start = Carbon::create($d->year, $d->month, $d->day, 9, 0);
        $end   = (clone $start)->addHours(8);

        return $this->state([
            'work_date'  => $d->toDateString(),
            'start_time' => $start,
            'end_time'   => $end,
            'status'     => defined(Attendance::class.'::STATUS_ENDED') ? Attendance::STATUS_ENDED : 'ended',
        ]);
    }

    /** ユーザー固定 */
    public function forUser(User $user): self
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    /** 日付固定（YYYY-MM-DD） */
    public function onDate(string $ymd): self
    {
        return $this->state(fn () => ['work_date' => $ymd]);
    }

    /** 作成後に休憩を自動生成（start/end があるときのみ）*/
    public function configure(): self
    {
        return $this->afterCreating(function (Attendance $attendance): void {
            if (empty($attendance->start_time) || empty($attendance->end_time)) {
                return; // 勤務外・出勤中は休憩を作らない
            }

            $start = Carbon::parse($attendance->start_time);
            $end   = Carbon::parse($attendance->end_time);

            // 昼休憩（45〜60分）— 70%
            if ($this->faker->boolean(70)) {
                $lunchStart = (clone $start)->setTime(12, 0)->addMinutes($this->faker->numberBetween(-15, 15));
                $lunchStart = max($lunchStart, (clone $start)->addHour()); // 出勤直後を避ける
                $lunchEnd   = (clone $lunchStart)->addMinutes($this->faker->randomElement([45, 60]));
                if ($lunchEnd < $end->copy()->subMinutes(15)) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $lunchStart, // ← migrationのカラム名に合わせる
                        'break_end'     => $lunchEnd,
                    ]);
                }
            }

            // 午後休憩（10〜20分）— 50%
            if ($this->faker->boolean(50)) {
                $pmStart = (clone $start)->setTime(15, 0)->addMinutes($this->faker->numberBetween(-15, 15));
                $pmStart = max($pmStart, (clone $start)->addMinutes(90));
                $pmEnd   = (clone $pmStart)->addMinutes($this->faker->numberBetween(10, 20));
                if ($pmEnd < $end) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $pmStart,
                        'break_end'     => $pmEnd,
                    ]);
                }
            }
        });
    }
}
