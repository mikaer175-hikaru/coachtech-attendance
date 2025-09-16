<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class StartBreakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $today = Carbon::today()->toDateString();

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('work_date', $today)
                ->first();

            if (!$attendance || !$attendance->start_time) {
                $validator->errors()->add('attendance', '出勤していないため、休憩できません。');
                return;
            }

            $hasOngoingBreak = $attendance->breakTimes()
                ->whereNull('end_time')
                ->exists();

            if ($hasOngoingBreak) {
                $validator->errors()->add('break', 'すでに休憩中です。');
            }
        });
    }
}

