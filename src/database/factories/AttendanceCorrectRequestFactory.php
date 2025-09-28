<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectRequestFactory extends Factory
{
    protected $model = AttendanceCorrectRequest::class;

    public function definition(): array
    {
        return [
            'attendance_id'  => Attendance::factory(),
            'user_id'        => User::factory(),
            'new_start_time' => null,
            'new_end_time'   => null,
            'new_breaks'     => [],
            'note'           => 'テスト修正申請',
            'status'         => AttendanceCorrectRequest::STATUS_PENDING,
            'approved_at'    => null,
            'rejected_at'    => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => ['status' => AttendanceCorrectRequest::STATUS_PENDING]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status'      => AttendanceCorrectRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn () => [
            'status'      => AttendanceCorrectRequest::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
    }
}
