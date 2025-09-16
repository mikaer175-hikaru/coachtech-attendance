<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class EndBreakRequest extends FormRequest
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

            if (!$attendance) {
                $validator->errors()->add('attendance', '出勤記録がありません。');
                return;
            }

            $ongoingBreak = $attendance->breakTimes()
                ->whereNull('end_time')
                ->first();

            if (!$ongoingBreak) {
                $validator->errors()->add('break', '休憩中ではありません。');
            }
        });
    }
}
