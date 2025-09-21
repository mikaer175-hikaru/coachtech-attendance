<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $note      = $this->input('note');
        $startTime = $this->input('start_time');
        $endTime   = $this->input('end_time');

        $trim = static fn ($v) => is_string($v) ? trim(preg_replace('/\s/u', ' ', $v)) : $v;

        $breaks = $this->input('breaks', []);
        if (!is_array($breaks)) {
            $breaks = [];
        } else {
            $breaks = array_values(array_map(function ($row) use ($trim) {
                $bs = $trim(Arr::get($row, 'start'));
                $be = $trim(Arr::get($row, 'end'));
                return [
                    'start' => ($bs === '' ? null : $bs),
                    'end'   => ($be === '' ? null : $be),
                ];
            }, $breaks));
        }

        $this->merge([
            'note'       => is_string($note) ? trim($note) : $note,
            'start_time' => ($startTime === '' ? null : $trim($startTime)),
            'end_time'   => ($endTime === '' ? null : $trim($endTime)),
            'breaks'     => $breaks,
        ]);
    }

    public function rules(): array
    {
        return [
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time'   => ['nullable', 'date_format:H:i'],
            'note'       => ['required', 'string', 'max:200'],
            'breaks'           => ['array', 'max:20'],
            'breaks.*.start'   => ['nullable', 'date_format:H:i'],
            'breaks.*.end'     => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $start = $this->input('start_time');
            $end   = $this->input('end_time');
            $rows  = $this->input('breaks', []);

            if ($start && $end && $start >= $end) {
                $v->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 休憩の検証
            foreach ($rows as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                if ($bs && $be && $bs >= $be) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }
                if ($start && $bs && $bs < $start) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }
                if ($end && $be && $be > $end) {
                    $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            $toMin = static function (?string $hm): ?int {
                if (!$hm || !preg_match('/^\d{2}:\d{2}$/', $hm)) return null;
                [$h, $m] = explode(':', $hm);
                return (int)$h * 60 + (int)$m;
            };

            $intervals = [];
            foreach ($rows as $i => $b) {
                $bs = $toMin($b['start'] ?? null);
                $be = $toMin($b['end'] ?? null);
                if ($bs !== null && $be !== null) {
                    $intervals[] = ['i' => $i, 's' => $bs, 'e' => $be];
                }
            }
            usort($intervals, fn($a, $b) => $a['s'] <=> $b['s']);
            for ($i = 1; $i < count($intervals); $i++) {
                $prev = $intervals[$i - 1];
                $curr = $intervals[$i];
                if ($curr['s'] < $prev['e']) {
                    $v->errors()->add("breaks.{$curr['i']}.start", '休憩時間が不適切な値です');
                }
            }

            if (!filled($this->input('note'))) {
                $v->errors()->add('note', '備考を記入してください');
            }
        });
    }

    public function messages(): array
    {
        return [
            'note.required'                 => '備考を記入してください',
            'note.max'                      => '備考は200文字以内で入力してください',
            'start_time.date_format'        => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.date_format'          => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.array'                  => '休憩の形式が不正です',
            'breaks.max'                    => '休憩は20件以内で入力してください',
            'breaks.*.start.date_format'    => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'      => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }
}
