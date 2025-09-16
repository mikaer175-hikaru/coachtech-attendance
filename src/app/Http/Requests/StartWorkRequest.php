<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class StartWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return []; // 入力項目なし
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $today = Carbon::today()->toDateString();

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('work_date', $today)
                ->first();

            if ($attendance && $attendance->start_time) {
                $validator->errors()->add('start_time', 'すでに出勤済みです。');
            }
        });
    }
}
