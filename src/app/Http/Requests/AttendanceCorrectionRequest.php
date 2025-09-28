<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
            'note'       => is_string($this->input('note')) ? trim($this->input('note')) : $this->input('note'),
            'start_time' => ($this->input('start_time') === '' ? null : $trim($this->input('start_time'))),
            'end_time'   => ($this->input('end_time') === '' ? null : $trim($this->input('end_time'))),
            'breaks'     => $breaks,
        ]);
    }

    public function rules(): array
    {
        $hm = ['nullable', 'regex:/^\d{1,2}:\d{2}$/'];

        return [
            'start_time'       => $hm,
            'end_time'         => $hm,
            'note'             => ['required', 'string', 'max:200'],
            'breaks'           => ['array', 'max:20'],
            'breaks.*.start'   => $hm,
            'breaks.*.end'     => $hm,
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
                if (!$hm || !preg_match('/^\d{1,2}:\d{2}$/', $hm)) return null;
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
                if ($intervals[$i]['s'] < $intervals[$i - 1]['e']) {
                    $v->errors()->add("breaks.{$intervals[$i]['i']}.start", '休憩時間が不適切な値です');
                }
            }

            if (!filled($this->input('note'))) {
                $v->errors()->add('note', '備考を記入してください');
            }

            // 最低1項目は入力必須
            $hasAny = filled($start) || filled($end)
                   || collect($rows)->contains(fn($b) => filled($b['start'] ?? null) || filled($b['end'] ?? null));
            if (!$hasAny) {
                $v->errors()->add('start_time', '出勤・退勤・休憩のいずれかを入力してください');
            }
        });
    }

    public function messages(): array
    {
        return [
            'note.required'                 => '備考を記入してください',
            'note.max'                      => '備考は200文字以内で入力してください',
            'start_time.regex'              => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.regex'                => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.array'                  => '休憩の形式が不正です',
            'breaks.max'                    => '休憩は20件以内で入力してください',
            'breaks.*.start.regex'          => '休憩時間が不適切な値です',
            'breaks.*.end.regex'            => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    public function scopeOwnedBy($q, int $userId) { return $q->where('user_id', $userId); }
    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
}
