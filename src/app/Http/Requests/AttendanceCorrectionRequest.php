<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');
        return auth()->check() && $attendance && (int)$attendance->user_id === (int)auth()->id();
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
            'note.required'              => '備考を記入してください。',
            'start_time.date_format'     => '出勤時間もしくは退勤時間が不適切な値です。',
            'end_time.date_format'       => '出勤時間もしくは退勤時間が不適切な値です。',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です。',
            'breaks.*.end.date_format'   => '休憩時間が不適切な値です。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $start = $this->toTime('start_time');
            $end   = $this->toTime('end_time');

            // 出勤・退勤の逆転
            if ($start && $end && $start->gte($end)) {
                $this->addErr($v, 'start_time', '出勤時間もしくは退勤時間が不適切な値です。');
                $this->addErr($v, 'end_time',   '出勤時間もしくは退勤時間が不適切な値です。');
            }

            // 休憩チェック（複数行も上にまとめて出せるよう breaks キーに積む）
            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) return;

            foreach ($breaks as $b) {
                $bs = isset($b['start']) ? $this->toTimeVal($b['start']) : null;
                $be = isset($b['end'])   ? $this->toTimeVal($b['end'])   : null;

                // 完全未入力行はスキップ
                if (!$bs && !$be) continue;

                // 欠け or 逆転 → 「休憩時間が不適切な値です。」
                if (!$bs || !$be || ($bs && $be && $bs->gte($be))) {
                    $this->addErr($v, 'breaks', '休憩時間が不適切な値です。');
                    continue;
                }

                if ($start && $end) {
                    // 終了 > 退勤 → 「休憩時間もしくは退勤時間が不適切な値です。」
                    if ($be->gt($end)) {
                        $this->addErr($v, 'breaks', '休憩時間もしくは退勤時間が不適切な値です。');
                    }
                    // 開始 < 出勤 または 開始 > 退勤 → 「休憩時間が不適切な値です。」
                    if ($bs->lt($start) || $bs->gt($end)) {
                        $this->addErr($v, 'breaks', '休憩時間が不適切な値です。');
                    }
                }
            }
        });
    }

    private function toTime(string $key): ?Carbon
    {
        return $this->toTimeVal($this->input($key));
    }

    private function toTimeVal(?string $v): ?Carbon
    {
        return $v ? Carbon::createFromFormat('H:i', $v) : null;
    }

    private function addErr(Validator $v, string $key, string $msg): void
    {
        // 同一メッセージの重複だけ抑止。異なる文言は積み上げる（上部で複数表示させる）
        $exists = $v->errors()->get($key) ?? [];
        if (!in_array($msg, $exists, true)) {
            $v->errors()->add($key, $msg);
        }
    }
}
