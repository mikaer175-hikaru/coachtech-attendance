<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time'         => ['nullable', 'date_format:H:i'],
            'end_time'           => ['nullable', 'date_format:H:i'],
            'note'               => ['required', 'string'],
            'breaks'             => ['array'],
            'breaks.*.start'     => ['nullable', 'date_format:H:i'],
            'breaks.*.end'       => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください。',

            'start_time.date_format'   => '出勤時間もしくは退勤時間が不適切な値です。',
            'end_time.date_format'     => '出勤時間もしくは退勤時間が不適切な値です。',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です。',
            'breaks.*.end.date_format'   => '休憩時間が不適切な値です。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $start = $this->t('start_time');
            $end   = $this->t('end_time');

            // 1) 出退勤の前後関係
            if ($start && $end && $start->gte($end)) {
                $this->addErr($v, 'start_time', '出勤時間もしくは退勤時間が不適切な値です。');
                $this->addErr($v, 'end_time',   '出勤時間もしくは退勤時間が不適切な値です。');
            }

            // 2) 休憩の勤務時間内チェック
            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) {
                return;
            }

            foreach ($breaks as $i => $b) {
                $bs = isset($b['start']) ? $this->tVal($b['start']) : null;
                $be = isset($b['end'])   ? $this->tVal($b['end'])   : null;

                // 完全未入力の行は無視
                if (!$bs && !$be) {
                    continue;
                }

                // どちらか欠け／逆転
                if (!$bs || !$be || ($bs && $be && $bs->gte($be))) {
                    $this->addErr($v, "breaks", '休憩時間が不適切な値です。');
                    continue;
                }

                // 退勤以後に終了 → 「休憩時間もしくは退勤時間が不適切な値です。」
                if ($end && $be->gt($end)) {
                    $this->addErr($v, "breaks", '休憩時間もしくは退勤時間が不適切な値です。');
                }
                // 出勤前に開始 → 「休憩時間が不適切な値です。」
                if ($start && $bs->lt($start)) {
                    $this->addErr($v, "breaks", '休憩時間が不適切な値です。');
                }
                // 退勤より後に開始 → 「休憩時間が不適切な値です。」
                if ($end && $bs->gt($end)) {
                    $this->addErr($v, "breaks", '休憩時間が不適切な値です。');
                }
            }
        });
    }

    private function t(string $key): ?Carbon
    {
        $v = $this->input($key);
        return $this->tVal($v);
    }

    private function tVal(?string $v): ?Carbon
    {
        return $v ? Carbon::createFromFormat('H:i', $v) : null;
    }

    private function addErr(Validator $v, string $key, string $msg): void
    {
        // 同一メッセージの重複だけ抑止、別メッセージは積み上げる
        $exists = $v->errors()->get($key) ?? [];
        if (!in_array($msg, $exists, true)) {
            $v->errors()->add($key, $msg);
        }
    }
}
