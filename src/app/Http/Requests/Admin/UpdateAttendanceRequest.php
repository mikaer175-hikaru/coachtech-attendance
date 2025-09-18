<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 管理者ミドルウェアで制御済み前提
    }

    public function rules(): array
    {
        return [
            'start_time'       => ['nullable', 'date_format:H:i'],
            'end_time'         => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time'   => ['nullable', 'date_format:H:i', 'after_or_equal:break_start_time'],
            'note'             => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.date_format'        => '出勤は「HH:MM」形式で入力してください',
            'end_time.date_format'          => '退勤は「HH:MM」形式で入力してください',
            'end_time.after_or_equal'       => '退勤は出勤以降の時刻にしてください',
            'break_start_time.date_format'  => '休憩開始は「HH:MM」形式で入力してください',
            'break_end_time.date_format'    => '休憩終了は「HH:MM」形式で入力してください',
            'break_end_time.after_or_equal' => '休憩終了は休憩開始以降の時刻にしてください',
            'note.max'                      => '備考は200文字以内で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $start = $this->t('start_time');
            $end   = $this->t('end_time');
            $brS   = $this->t('break_start_time');
            $brE   = $this->t('break_end_time');

            if ($start && $end && $start->gte($end)) {
                $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                $v->errors()->add('end_time',   '出勤時間もしくは退勤時間が不適切な値です');
            }
            if ($brS) {
                if ($start && $brS->lt($start)) $v->errors()->add('break_start_time', '休憩時間が不適切な値です');
                if ($end && $brS->gt($end))     $v->errors()->add('break_start_time', '休憩時間が不適切な値です');
            }
            if ($brE && $end && $brE->gt($end)) {
                $v->errors()->add('break_end_time', '休憩時間もしくは退勤時間が不適切な値です');
            }
        });
    }

    private function t(string $key): ?Carbon
    {
        $v = $this->input($key);
        return $v ? Carbon::createFromFormat('H:i', $v) : null;
    }
}
