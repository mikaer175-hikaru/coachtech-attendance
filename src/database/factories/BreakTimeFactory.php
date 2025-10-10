<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        $t = Carbon::now()->startOfHour();

        return [
            'attendance_id' => Attendance::factory(),
            'break_start'   => $t,
            'break_end'     => (clone $t)->addMinutes(15),
        ];
    }
}
