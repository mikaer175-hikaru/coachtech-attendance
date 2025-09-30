<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator as ValidationValidator;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');
        return auth()->check() && $attendance && (int)$attendance->user_id === (int)auth()->id();
    }

    public function rules(): array
    {
        return [
            'start_time'        => ['nullable', 'date_format:H:i'],
            'end_time'          => ['nullable', 'date_format:H:i'],
            'note'              => ['required', 'string', 'max:200'],

            // 複数休憩（配列）
            'breaks'            => ['array'],
            'breaks.*.start'    => ['nullable', 'date_format:H:i'],
            'breaks.*.end'      => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.date_format'         => '出勤は「HH:MM」形式で入力してください',
            'end_time.date_format'           => '退勤は「HH:MM」形式で入力してください',
            'note.required'                  => '備考を入力してください',
            'note.max'                       => '備考は200文字以内で入力してください',
            'breaks.*.start.date_format'     => '休憩開始は「HH:MM」形式で入力してください',
            'breaks.*.end.date_format'       => '休憩終了は「HH:MM」形式で入力してください',
        ];
    }

    // 管理者用と同じく after() で相関チェックをまとめる
    public function after(): array
    {
        return [
            function (ValidationValidator $v) {
                $attendance = $this->route('attendance');

                // 承認待ちは編集不可（仕様）
                if (($attendance->status ?? null) === 'pending') {
                    $v->errors()->add('base', '承認待ちの勤怠は編集できません');
                    return; // アーリーリターン
                }

                $start = $this->t('start_time');
                $end   = $this->t('end_time');

                // 出勤 < 退勤
                if ($start && $end && $start->gte($end)) {
                    $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                    $v->errors()->add('end_time',   '出勤時間もしくは退勤時間が不適切な値です');
                }

                // 休憩（配列）: 開始<終了、かつ 退勤を超えない
                $breaks = $this->input('breaks', []);
                foreach ($breaks as $idx => $b) {
                    $bs = $this->t($b['start'] ?? null, true);
                    $be = $this->t($b['end']   ?? null, true);

                    if ($bs && $be && $bs->gte($be)) {
                        $v->errors()->add("breaks.$idx.start", '休憩時間が不適切な値です');
                        $v->errors()->add("breaks.$idx.end",   '休憩時間が不適切な値です');
                    }
                    if ($end && $bs && $bs->gte($end)) {
                        $v->errors()->add("breaks.$idx.start", '休憩開始時間が退勤時間以降です');
                    }
                    if ($end && $be && $be->gt($end)) {
                        $v->errors()->add("breaks.$idx.end", '休憩終了時間が退勤時間を超過しています');
                    }
                }
            },
        ];
    }

    /**
     * HH:MM → Carbon 変換（null許容）
     * @param string|null $keyOrValue form input key or raw value when $raw=true
     */
    private function t(?string $keyOrValue, bool $raw = false): ?Carbon
    {
        $v = $raw ? $keyOrValue : $this->input($keyOrValue);
        return $v ? Carbon::createFromFormat('H:i', $v) : null;
    }
}
