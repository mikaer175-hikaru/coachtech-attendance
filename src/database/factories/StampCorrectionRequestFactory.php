<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    protected $model = StampCorrectionRequest::class;

    public function definition(): array
    {
        return [
            'attendance_id'  => Attendance::factory(),
            'user_id'        => User::factory(),
            'new_start_time' => null,
            'new_end_time'   => null,
            'new_breaks'     => [],
            'note'           => 'テスト修正申請',
            'status'         => StampCorrectionRequest::STATUS_PENDING,
            'approved_at'    => null,
            'rejected_at'    => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => [
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status'      => StampCorrectionRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn () => [
            'status'      => StampCorrectionRequest::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
    }
}
