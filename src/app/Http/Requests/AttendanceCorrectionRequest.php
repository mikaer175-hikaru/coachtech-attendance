<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'start_time' => ['nullable','date_format:H:i'],
            'end_time'   => ['nullable','date_format:H:i'],
            'breaks'     => ['array'],
            'breaks.*.start' => ['nullable','date_format:H:i'],
            'breaks.*.end'   => ['nullable','date_format:H:i'],
            'note'       => ['required','string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $start = $this->input('start_time');
            $end   = $this->input('end_time');

            // 出勤・退勤の相互関係（FN029-1）
            if ($start && $end && $start >= $end) {
                $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩の検証（FN029-2,3）
            foreach ($this->input('breaks', []) as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                if ($start && $bs && $bs < $start) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }
                if ($end && $be && $be > $end) {
                    $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($bs && $be && $bs >= $be) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }
            }

            // 備考必須（FN029-4）
            if (!filled($this->input('note'))) {
                $v->errors()->add('note', '備考を記入してください');
            }
        });
    }

    public function messages(): array
    {
        // 追加で明示する分（要件の文言を厳守）
        return [
            'note.required'      => '備考を記入してください',
            'start_time.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.date_format'   => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'   => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }
}
