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
            'end_time'         => ['nullable', 'date_format:H:i'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time'   => ['nullable', 'date_format:H:i'],
            'note'             => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
            'start_time.date_format'       => '出勤時間は「HH:MM」形式で入力してください',
            'end_time.date_format'         => '退勤時間は「HH:MM」形式で入力してください',
            'break_start_time.date_format' => '休憩時間が不適切な値です',
            'break_end_time.date_format'   => '休憩時間が不適切な値です',
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
