<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class EndWorkRequest extends FormRequest
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
                $validator->errors()->add('attendance', '出勤記録が存在しません。');
            }

            if ($attendance && $attendance->end_time) {
                $validator->errors()->add('attendance', 'すでに退勤済みです。');
            }
        });
    }
}
